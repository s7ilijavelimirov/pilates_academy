<?php
if (!defined('ABSPATH')) exit;

class PA_Exercise_Importer
{

    private $debug_log = [];

    public function render_page()
    {
?>
        <div class="wrap">
            <h1>Import Pilates Exercises</h1>

            <?php if (isset($_POST['pa_import_submit'])): ?>
                <?php $this->process_import(); ?>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pa_import_action', 'pa_import_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="json_file">JSON File</label></th>
                        <td>
                            <input type="file" name="json_file" id="json_file" accept=".json" required>
                            <p class="description">Upload JSON file with exercise data</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="videos_zip">Videos ZIP</label></th>
                        <td>
                            <input type="file" name="videos_zip" id="videos_zip" accept=".zip">
                            <p class="description">Upload ZIP with video files</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="pa_import_submit" class="button button-primary" value="Import Exercises">
                </p>
            </form>

            <div style="background: #f0f0f1; padding: 15px; margin-top: 20px;">
                <h3>JSON Format:</h3>
                <pre style="background: white; padding: 10px;"><code>[
  {
    "title": {
      "en": "Roll Up",
      "de": "Aufrollen",
      "uk": "Скручування"
    },
    "video_file": "roll-up.mp4",
    "position": "supine"
  }
]</code></pre>
                <p><strong>Fields:</strong></p>
                <ul>
                    <li><code>title</code> - Object with EN, DE, UK translations</li>
                    <li><code>video_file</code> - Video filename in ZIP</li>
                    <li><code>position</code> - Exercise position slug (e.g., "supine", "prone")</li>
                </ul>
            </div>
        </div>
<?php
    }

    private function process_import()
    {
        $this->debug_log[] = "=== START IMPORT ===";

        // DEBUG upload limita
        $this->debug_log[] = "PHP upload_max_filesize: " . ini_get('upload_max_filesize');
        $this->debug_log[] = "PHP post_max_size: " . ini_get('post_max_size');
        $this->debug_log[] = "PHP max_execution_time: " . ini_get('max_execution_time');

        // DEBUG $_FILES
        if (isset($_FILES['videos_zip'])) {
            $this->debug_log[] = "videos_zip in \$_FILES: YES";
            $this->debug_log[] = "videos_zip name: " . $_FILES['videos_zip']['name'];
            $this->debug_log[] = "videos_zip size: " . $_FILES['videos_zip']['size'] . " bytes";
            $this->debug_log[] = "videos_zip error: " . $_FILES['videos_zip']['error'];
            $this->debug_log[] = "videos_zip tmp_name: " . $_FILES['videos_zip']['tmp_name'];
        } else {
            $this->debug_log[] = "videos_zip NOT in \$_FILES - File not uploaded!";
        }

        if (!isset($_POST['pa_import_nonce']) || !wp_verify_nonce($_POST['pa_import_nonce'], 'pa_import_action')) {
            echo '<div class="error"><p>Security check failed.</p></div>';
            return;
        }

        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error"><p>No JSON file uploaded.</p></div>';
            return;
        }

        // Read JSON
        $json_content = file_get_contents($_FILES['json_file']['tmp_name']);
        $this->debug_log[] = "JSON Content: " . substr($json_content, 0, 200);

        $exercises = json_decode($json_content, true);

        if (!$exercises || !is_array($exercises)) {
            echo '<div class="error"><p>Invalid JSON format.</p></div>';
            $this->show_debug();
            return;
        }

        $this->debug_log[] = "Found " . count($exercises) . " exercises in JSON";

        // Extract ZIP if provided
        $video_files = [];
        if (isset($_FILES['videos_zip']) && $_FILES['videos_zip']['error'] === UPLOAD_ERR_OK) {
            $this->debug_log[] = "ZIP file uploaded: " . $_FILES['videos_zip']['name'];
            $video_files = $this->extract_videos_zip($_FILES['videos_zip']['tmp_name']);
            $this->debug_log[] = "Extracted videos: " . implode(', ', array_keys($video_files));
        } else {
            $this->debug_log[] = "No ZIP file uploaded";
        }

        $imported = 0;
        $errors = [];

        foreach ($exercises as $index => $exercise) {
            // Pripremi title za prikaz
            $title_display = 'NO TITLE';
            if (isset($exercise['title'])) {
                if (is_array($exercise['title'])) {
                    $title_display = $exercise['title']['en'] ?? reset($exercise['title']);
                } else {
                    $title_display = $exercise['title'];
                }
            }
            $this->debug_log[] = "\n--- Processing: " . $title_display . " ---";

            // SAMO JEDAN try-catch blok
            try {
                $post_id = $this->import_single_exercise($exercise, $video_files);
                if ($post_id) {
                    $imported++;
                    $this->debug_log[] = "✓ Created post ID: $post_id";
                }
            } catch (Exception $e) {
                $errors[] = $title_display . ': ' . $e->getMessage();
                $this->debug_log[] = "✗ Error: " . $e->getMessage();
            }
        }

        // Cleanup extracted files
        if (!empty($video_files)) {
            $this->debug_log[] = "Cleaning up temp files...";

            // Obriši sve fajlove
            foreach ($video_files as $file_path) {
                @unlink($file_path);
            }

            // Obriši folder
            if (!empty($video_files)) {
                $first_file = reset($video_files);
                $temp_folder = dirname($first_file);

                // Rekurzivno obriši folder
                $this->remove_directory($temp_folder);
                $this->debug_log[] = "Removed temp folder: $temp_folder";
            }
        }

        echo '<div class="updated"><p>';
        echo sprintf('✓ Successfully imported: <strong>%d</strong> exercises', $imported);
        echo '</p></div>';

        if (!empty($errors)) {
            echo '<div class="error"><p><strong>Errors:</strong></p><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }

        $this->show_debug();
    }

    private function extract_videos_zip($zip_path)
    {
        $this->debug_log[] = "Extracting ZIP...";

        $zip = new ZipArchive;
        $video_files = [];

        if ($zip->open($zip_path) === TRUE) {
            // Koristi WordPress upload folder umesto sys_get_temp_dir()
            $upload_dir = wp_upload_dir();
            $extract_path = $upload_dir['basedir'] . '/pa_temp_videos_' . time();

            // Kreiraj folder
            if (!wp_mkdir_p($extract_path)) {
                $this->debug_log[] = "Failed to create extract folder: $extract_path";
                return $video_files;
            }

            $this->debug_log[] = "Extract path: $extract_path";
            $this->debug_log[] = "ZIP contains " . $zip->numFiles . " files";

            // Lista svih fajlova u ZIP-u
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $this->debug_log[] = "  ZIP[$i]: " . $stat['name'];
            }

            // Ekstraktuj
            if (!$zip->extractTo($extract_path)) {
                $this->debug_log[] = "Failed to extract ZIP";
                $zip->close();
                return $video_files;
            }

            $zip->close();
            $this->debug_log[] = "ZIP extracted successfully";

            // Traži video fajlove - jednostavno skeniranje
            $this->debug_log[] = "Scanning extracted files...";

            $scanned_files = $this->scan_directory_for_videos($extract_path);

            foreach ($scanned_files as $file_path) {
                $basename = basename($file_path);
                $video_files[$basename] = $file_path;
                $this->debug_log[] = "  ✓ Video: $basename (" . filesize($file_path) . " bytes)";
            }

            $this->debug_log[] = "Total videos found: " . count($video_files);
        } else {
            $this->debug_log[] = "Failed to open ZIP file";
        }

        return $video_files;
    }
    private function scan_directory_for_videos($dir)
    {
        $videos = [];

        if (!is_dir($dir)) {
            return $videos;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                // Rekurzivno skeniranje podfoldera
                $videos = array_merge($videos, $this->scan_directory_for_videos($path));
            } elseif (is_file($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) {
                    $videos[] = $path;
                }
            }
        }

        return $videos;
    }
    private function remove_directory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->remove_directory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
    private function import_single_exercise($data, $video_files)
    {
        // Ako nema title, koristi folder ime
        $titles = [];

        if (!empty($data['title']) && is_array($data['title'])) {
            // Ima custom prevode
            $titles = $data['title'];
            $main_title = isset($titles['en']) ? $titles['en'] : reset($titles);
        } elseif (!empty($data['title'])) {
            // Single string title
            $main_title = $data['title'];
            $titles = [
                'en' => $data['title'],
                'de' => $data['title'],
                'uk' => $data['title']
            ];
        } elseif (!empty($data['folder'])) {
            // Koristi folder name kao title za sva 3 jezika
            $main_title = $data['folder'];
            $titles = [
                'en' => $data['folder'],
                'de' => $data['folder'],
                'uk' => $data['folder']
            ];
        } else {
            throw new Exception('No title or folder provided');
        }

        $this->debug_log[] = "Titles: " . json_encode($titles);

        // Check if exists (samo EN verzija)
        $existing_query = new WP_Query([
            'post_type'      => 'pilates_exercise',
            'title'          => $titles['en'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'lang'           => 'en'
        ]);

        if ($existing_query->have_posts()) {
            throw new Exception('Already exists (skipped)');
        }

        // Handle video(s) - Case-insensitive search
        $video_ids = [];

        // NOVI FORMAT: Folder sa video fajlom unutar
        if (!empty($data['folder'])) {
            $folder_name = $data['folder'];

            $this->debug_log[] = "Looking for videos in folder: $folder_name";

            // Case-insensitive search - pronađi sve video fajlove koji počinju sa folder imenom
            $found_videos = [];
            foreach ($video_files as $basename => $file_path) {
                // Case-insensitive poređenje
                $basename_lower = strtolower($basename);
                $folder_lower = strtolower($folder_name);

                // Proveri da li video počinje sa folder imenom
                if (strpos($basename_lower, $folder_lower) === 0) {
                    $found_videos[] = ['name' => $basename, 'path' => $file_path];
                    $this->debug_log[] = "  Match found: $basename";
                }
            }

            if (empty($found_videos)) {
                $this->debug_log[] = "No videos found for folder. Available: " . implode(', ', array_keys($video_files));
                throw new Exception("No videos found for folder: $folder_name");
            }

            // Import svih pronađenih videa
            foreach ($found_videos as $video) {
                $this->debug_log[] = "Importing video: {$video['name']}";

                try {
                    $video_id = $this->import_local_video($video['path'], 0, $main_title);
                    $video_ids[] = $video_id;
                    $this->debug_log[] = "Video imported to Media Library: ID $video_id";
                } catch (Exception $e) {
                    $this->debug_log[] = "Video import failed: " . $e->getMessage();
                }
            }
        }
        // FORMAT: Multiple videos array
        elseif (!empty($data['videos']) && is_array($data['videos'])) {
            $this->debug_log[] = "Multiple videos requested: " . count($data['videos']);

            foreach ($data['videos'] as $video_file) {
                if (isset($video_files[$video_file])) {
                    $this->debug_log[] = "Video found in ZIP: $video_file";

                    try {
                        $video_id = $this->import_local_video($video_files[$video_file], 0, $main_title);
                        $video_ids[] = $video_id;
                        $this->debug_log[] = "Video imported to Media Library: ID $video_id";
                    } catch (Exception $e) {
                        $this->debug_log[] = "Video import failed: " . $e->getMessage();
                    }
                } else {
                    $this->debug_log[] = "Video NOT found in ZIP: $video_file";
                }
            }
        }
        // STARI FORMAT: Single video_file
        elseif (!empty($data['video_file']) && isset($video_files[$data['video_file']])) {
            $this->debug_log[] = "Single video found in ZIP: " . $data['video_file'];

            try {
                $video_id = $this->import_local_video($video_files[$data['video_file']], 0, $main_title);
                $video_ids[] = $video_id;
                $this->debug_log[] = "Video imported to Media Library: ID $video_id";
            } catch (Exception $e) {
                $this->debug_log[] = "Video import failed: " . $e->getMessage();
                throw $e;
            }
        } else {
            $this->debug_log[] = "No videos found in ZIP";
            throw new Exception('Video file(s) not found in ZIP');
        }

        if (empty($video_ids)) {
            throw new Exception('Failed to import any videos');
        }

        $this->debug_log[] = "Total videos imported: " . count($video_ids);

        // Kreiraj postove za sve 3 jezika
        $languages = ['en', 'de', 'uk'];
        $post_ids = [];

        foreach ($languages as $lang) {
            // Preskoči ako nema prevoda za taj jezik
            if (!isset($titles[$lang]) || empty($titles[$lang])) {
                $this->debug_log[] = "Skipping $lang - no title provided";
                continue;
            }

            $this->debug_log[] = "\nCreating $lang version: " . $titles[$lang];

            // Create post sa prevedenim title-om
            $post_data = [
                'post_title'   => sanitize_text_field($titles[$lang]),
                'post_type'    => 'pilates_exercise',
                'post_status'  => 'publish',
            ];

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $this->debug_log[] = "Failed to create $lang post: " . $post_id->get_error_message();
                continue;
            }

            $this->debug_log[] = "Post created: ID $post_id ($lang) - '{$titles[$lang]}'";

            // Set Polylang language
            pll_set_post_language($post_id, $lang);
            $this->debug_log[] = "Language set to: $lang";

            // Add video(s) to ACF repeater
            if (!empty($video_ids)) {
                $repeater_value = [];

                // Dodaj svaki video kao novi red u repeateru
                foreach ($video_ids as $video_id) {
                    $repeater_value[] = array(
                        'video' => $video_id,
                        'subtitles' => array(),
                        'text' => ''
                    );
                }

                $result = update_field('exercise_video_sections', $repeater_value, $post_id);
                $this->debug_log[] = "ACF videos added (" . count($video_ids) . " sections): " . ($result ? 'SUCCESS' : 'FAILED');
            }

            // Set exercise_position taxonomy
            if (!empty($data['position'])) {
                $position_slug = sanitize_text_field($data['position']);

                // Prvo pronađi EN term
                $term = get_term_by('slug', $position_slug, 'exercise_position');

                if ($term) {
                    // Ako radimo sa DE ili UK postom, nađi prevedeni term
                    if ($lang !== 'en' && function_exists('pll_get_term')) {
                        $translated_term_id = pll_get_term($term->term_id, $lang);
                        if ($translated_term_id) {
                            wp_set_object_terms($post_id, (int)$translated_term_id, 'exercise_position');
                            $this->debug_log[] = "Position set ($lang): $translated_term_id";
                        } else {
                            // Fallback na EN term ako nema prevod
                            wp_set_object_terms($post_id, $term->term_id, 'exercise_position');
                            $this->debug_log[] = "Position set (EN fallback): {$term->term_id}";
                        }
                    } else {
                        // Za EN verziju koristi direktno
                        wp_set_object_terms($post_id, $term->term_id, 'exercise_position');
                        $this->debug_log[] = "Position set (en): {$term->term_id}";
                    }
                } else {
                    $this->debug_log[] = "Warning: Position term '$position_slug' not found in database";
                }
            }

            $post_ids[$lang] = $post_id;
        }

        // Poveži sve prevode kroz Polylang
        if (count($post_ids) >= 2) {
            $this->debug_log[] = "\nLinking translations...";
            pll_save_post_translations($post_ids);

            $links = [];
            foreach ($post_ids as $lang => $id) {
                $links[] = "$lang=$id";
            }
            $this->debug_log[] = "Translations linked: " . implode(', ', $links);
        }

        // Vrati EN post ID kao glavni (ili prvi dostupan)
        return isset($post_ids['en']) ? $post_ids['en'] : reset($post_ids);
    }
    private function import_local_video($file_path, $post_id, $title)
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $this->debug_log[] = "Importing video from: $file_path";
        $this->debug_log[] = "File exists: " . (file_exists($file_path) ? 'YES' : 'NO');
        $this->debug_log[] = "File size: " . filesize($file_path) . " bytes";

        // Create temp copy
        $tmp_file = wp_tempnam($file_path);
        copy($file_path, $tmp_file);

        $this->debug_log[] = "Temp file created: $tmp_file";

        $file_array = [
            'name'     => basename($file_path),
            'tmp_name' => $tmp_file
        ];

        $this->debug_log[] = "Calling media_handle_sideload...";

        // Ako je post_id 0, video se ne vezuje ni za jedan post (kasnije ćemo ga vezati)
        $video_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($video_id)) {
            @unlink($tmp_file);
            $error_msg = $video_id->get_error_message();
            $this->debug_log[] = "media_handle_sideload ERROR: $error_msg";
            throw new Exception('Video upload failed: ' . $error_msg);
        }

        $this->debug_log[] = "Video uploaded successfully: ID $video_id";

        return $video_id;
    }

    private function show_debug()
    {
        echo '<div style="background: #fff; border: 1px solid #ccc; padding: 15px; margin-top: 20px; max-height: 500px; overflow-y: scroll;">';
        echo '<h3>Debug Log:</h3>';
        echo '<pre style="font-size: 11px;">';
        echo implode("\n", $this->debug_log);
        echo '</pre>';
        echo '</div>';
    }
}
