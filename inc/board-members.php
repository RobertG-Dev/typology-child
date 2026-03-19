<?php
/**
 * inc/board-members.php
 * Handles Backend Meta Box and Frontend Display Logic
 */

/* -------------------------------------------------------------------------- */
/* 1. ASSET ENQUEUE (Frontend)
/* -------------------------------------------------------------------------- */

function typology_child_enqueue_board_assets()
{
    // Only load assets on pages with the specific category
    if (is_page() && has_category('tarybos-nariai')) {
        $style_path     = get_stylesheet_directory() . '/assets/css/board-style.css';
        $style_uri_path = get_stylesheet_directory_uri() . '/assets/css/board-style.css';

        wp_enqueue_style(
            'board-members-style',
            $style_uri_path,
            [],
            file_exists($style_path) ? filemtime($style_path) : '1.0.1'
        );

        $script_path     = get_stylesheet_directory() . '/assets/js/board-script.js';
        $script_uri_path = get_stylesheet_directory_uri() . '/assets/js/board-script.js';
        wp_enqueue_script(
            'board-members-script',
            $script_uri_path,
            [],
            file_exists($script_path) ? filemtime($script_path) : '1.0.1',
            true
        );
    }
}

add_action('wp_enqueue_scripts', 'typology_child_enqueue_board_assets');


/* -------------------------------------------------------------------------- */
/* 2. ADMIN META BOX
/* -------------------------------------------------------------------------- */

function typology_child_add_board_metabox()
{
    add_meta_box(
        'board_members_box',
        'Tarybos Nariai',
        'typology_child_render_board_metabox',
        'page',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'typology_child_add_board_metabox');

function typology_child_render_board_metabox($post)
{
    wp_enqueue_editor();

    $members = get_post_meta($post->ID, '_board_members_data', true);
    wp_nonce_field('save_board_members', 'board_members_nonce');
    ?>

    <div id="board-members-wrapper">
        <div id="board-members-list">
            <?php
            if (!empty($members) && is_array($members)) {
                foreach ($members as $index => $member) {
                    typology_child_render_row($index, $member);
                }
            }
            ?>
        </div>
        <p>
            <button type="button" class="button button-primary" id="add-board-member">Add Board Member</button>
        </p>
    </div>

    <!-- Hidden Template -->
    <div id="board-member-template" style="display:none;">
        <?php typology_child_render_row('TEMPLATE_INDEX', array('name' => '', 'desc' => '', 'img_id' => '')); ?>
    </div>

    <!-- Admin Styles -->
    <style>
        .board-member-row {
            border: 1px solid #ccc;
            background: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: flex;
            gap: 20px;
        }

        .board-col-left {
            width: 150px;
            text-align: center;
            flex-shrink: 0;
        }

        .board-col-right {
            flex-grow: 1;
            min-width: 0;
        }

        .board-img-preview {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            background: #eee;
            min-height: 100px;
            object-fit: cover;
        }

        .board-input-row {
            margin-bottom: 15px;
        }

        .board-input-row label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .board-input-row input[type="text"] {
            width: 100%;
            padding: 5px;
        }

        .remove-board-member {
            color: #a00;
            text-decoration: none;
            border: 1px solid #a00;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 10px;
        }

        .remove-board-member:hover {
            background: #a00;
            color: #fff;
        }

        .wp-editor-wrap {
            width: 100%;
        }
    </style>

    <!-- Admin Script -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {

        // Selectors
        const list = document.getElementById('board-members-list');
        const addButton = document.getElementById('add-board-member');
        const template = document.getElementById('board-member-template');

        /**
         * Helper: Initialize WP Editor (TinyMCE)
         */
        function initWPEditor(id) {
          const settings = {
            tinymce: {
              wpautop: true,
              toolbar1: 'bold,italic,bullist,numlist,link,unlink,undo,redo'
            },
            quicktags: true,
            mediaButtons: false
          };

          if (window.wp && window.wp.editor) {
            window.wp.editor.initialize(id, settings);
          }
        }

        /**
         * Helper: Remove WP Editor instance
         */
        function removeWPEditor(id) {
          if (window.wp && window.wp.editor) {
            window.wp.editor.remove(id);
          }
        }

        /**
         * 1. ADD ROW
         */
        if (addButton && template) {
          addButton.addEventListener('click', function() {
            // Generate unique ID based on timestamp
            const uniqueId = new Date().getTime();

            // Get template HTML and replace the placeholder index
            let htmlContent = template.innerHTML.replace(/TEMPLATE_INDEX/g, uniqueId);

            // Insert the new row at the end of the list
            list.insertAdjacentHTML('beforeend', htmlContent);

            // Initialize the WP Editor for this new row
            // The textarea ID was set to 'board_desc_' + uniqueId in the PHP replacement
            initWPEditor('board_desc_' + uniqueId);
          });
        }

        /**
         * EVENT DELEGATION
         * Because rows are added dynamically, we listen for clicks on the parent 'list'
         * and check which element was actually clicked.
         */
        if (list) {
          list.addEventListener('click', function(e) {

            const target = e.target;

            // 2. REMOVE ROW
            if (target.matches('.remove-board-member')) {
              e.preventDefault();

              if (confirm('Are you sure?')) {
                const row = target.closest('.board-member-row');

                // Find the textarea within this row to clean up TinyMCE
                const textarea = row.querySelector('.board-wysiwyg');
                if (textarea) {
                  removeWPEditor(textarea.id);
                }

                // Remove the DOM element
                row.remove();
              }
              return;
            }

            // 3. MEDIA UPLOADER (Open Frame)
            if (target.matches('.upload-board-img')) {
              e.preventDefault();

              const colLeft = target.closest('.board-col-left');

              // Create the media frame
              const frame = wp.media({
                title: 'Select Image',
                button: {text: 'Use this image'},
                multiple: false
              });

              // When an image is selected, run a callback
              frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();

                // Set Hidden ID Input
                colLeft.querySelector('.board_img_id').value = attachment.id;

                // Set and Show Preview Image
                const preview = colLeft.querySelector('.board-img-preview');
                preview.src = attachment.url;
                preview.style.display = 'block';

                // Show Remove Button
                colLeft.querySelector('.remove-board-img').style.display = 'inline-block';
              });

              frame.open();
              return;
            }

            // 4. REMOVE IMAGE
            if (target.matches('.remove-board-img')) {
              e.preventDefault();

              const colLeft = target.closest('.board-col-left');

              // Clear ID Input
              colLeft.querySelector('.board_img_id').value = '';

              // Clear and Hide Preview
              const preview = colLeft.querySelector('.board-img-preview');
              preview.src = '';
              preview.style.display = 'none';

              // Hide Remove Button
              target.style.display = 'none';
            }
          });
        }
      });
    </script>
    <?php
}

function typology_child_render_row($index, $data)
{
    $name      = isset($data['name']) ? esc_attr($data['name']) : '';
    $desc      = isset($data['desc']) ? wp_kses_post($data['desc']) : '';
    $img_id    = isset($data['img_id']) ? esc_attr($data['img_id']) : '';
    $editor_id = 'board_desc_' . $index;

    $img_url = '';
    if ($img_id) {
        $img_src = wp_get_attachment_image_src($img_id, 'thumbnail');
        $img_url = $img_src ? $img_src[0] : '';
    }
    $display_img    = $img_url ? 'block' : 'none';
    $display_remove = $img_url ? 'inline-block' : 'none';
    ?>
    <div class="board-member-row">
        <div class="board-col-left">
            <img src="<?php echo $img_url; ?>" class="board-img-preview" style="display:<?php echo $display_img; ?>;">
            <input type="hidden" name="board_members[<?php echo $index; ?>][img_id]" class="board_img_id" value="<?php echo $img_id; ?>">
            <button type="button" class="button upload-board-img">Select Image</button>
            <br>
            <a href="#" class="remove-board-img" style="display:<?php echo $display_remove; ?>; font-size:10px; margin-top:5px;">Remove Image</a>
        </div>
        <div class="board-col-right">
            <div class="board-input-row">
                <label>Name</label>
                <input type="text" name="board_members[<?php echo $index; ?>][name]" value="<?php echo $name; ?>" placeholder="Full Name">
            </div>
            <div class="board-input-row">
                <label>Description</label>
                <?php
                if ($index === 'TEMPLATE_INDEX') {
                    echo '<textarea id="' . $editor_id . '" name="board_members[' . $index . '][desc]" class="board-wysiwyg" rows="6" style="width:100%">' . $desc . '</textarea>';
                } else {
                    wp_editor($data['desc'], $editor_id, array(
                        'textarea_name' => 'board_members[' . $index . '][desc]',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny'         => true,
                        'quicktags'     => true
                    ));
                }
                ?>
            </div>
            <a href="#" class="remove-board-member">Remove Member</a>
        </div>
    </div>
    <?php
}


/* -------------------------------------------------------------------------- */
/* 3. SAVE DATA
/* -------------------------------------------------------------------------- */

function typology_child_save_board_members($post_id)
{
    if (!isset($_POST['board_members_nonce']) || !wp_verify_nonce($_POST['board_members_nonce'], 'save_board_members')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    if (isset($_POST['board_members']) && is_array($_POST['board_members'])) {
        $clean_data = array();
        foreach ($_POST['board_members'] as $member) {
            if ($member['name'] && $member['name'] !== 'TEMPLATE_INDEX') {
                $clean_data[] = array(
                    'name'   => sanitize_text_field($member['name']),
                    'desc'   => wp_kses_post($member['desc']),
                    'img_id' => sanitize_text_field($member['img_id']),
                );
            }
        }
        update_post_meta($post_id, '_board_members_data', $clean_data);
    } else {
        delete_post_meta($post_id, '_board_members_data');
    }
}

add_action('save_post', 'typology_child_save_board_members');


/* -------------------------------------------------------------------------- */
/* 4. FRONTEND OUTPUT (FILTER)
/* -------------------------------------------------------------------------- */

function my_child_theme_board_member_content($content)
{
    if (is_page() && has_category('tarybos-nariai')) {
        $members = get_post_meta(get_the_ID(), '_board_members_data', true);

        if (empty($members) || !is_array($members)) {
            return $content;
        }

        ob_start();
        ?>

        <div class="board-accordion-wrapper">
            <section id="board-accordion-list">
                <?php foreach ($members as $key => $member):
                    $name = esc_html($member['name']);
                    $desc = wp_kses_post($member['desc']);
                    $img_id = absint($member['img_id']);
                    $img_src = $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
                    $row_id = sanitize_title($name);
                    ?>

                    <details id="<?php echo esc_attr($row_id); ?>">
                        <summary>
                            <span class="summary-title"><?php echo $name; ?></span>
                            <!-- Copy Link Button -->
                            <button type='button' class='copy-link-btn' aria-label="Copy link to <?php echo $name; ?>" data-id="<?php echo esc_attr($row_id); ?>">
                                <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                    <path d='M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71'></path>
                                    <path d='M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71'></path>
                                </svg>
                                <span class='copy-tooltip'>Copied!</span>
                            </button>
                            <div class="summary-actions">
                                <span class="toggle-icon"></span>
                            </div>
                        </summary>
                        <div>
                            <div class="inner-content">
                                <?php if ($img_src): ?>
                                    <img src="<?php echo esc_url($img_src); ?>" alt="<?php echo esc_attr($name); ?>" draggable="false" />
                                <?php endif; ?>

                                <div class="desc-wrap">
                                    <?php echo wpautop($desc); ?>
                                </div>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </section>
        </div>

        <?php
        return ob_get_clean();
    }

    return $content;
}

add_filter('the_content', 'my_child_theme_board_member_content', 20);