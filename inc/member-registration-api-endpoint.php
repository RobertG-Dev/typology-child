<?php

add_action('wpcf7_before_send_mail', 'send_to_members_dashboard');

function send_to_members_dashboard($contact_form)
{
    if ($contact_form->id() != 1615) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();

    if (!$submission) {
        return;
    }

    $data = $submission->get_posted_data();

    $member_data = [
        'first_name'  => sanitize_text_field($data['form-name'] ?? ''),
        'last_name'   => sanitize_text_field($data['form-last-name'] ?? ''),
        'email'       => sanitize_email($data['email'] ?? ''),
        'phone'       => sanitize_text_field($data['phone'] ?? ''),
        'workplace'   => sanitize_text_field($data['workplace'] ?? ''),
        'city'        => sanitize_text_field($data['city'] ?? ''),
        'choir_title' => sanitize_text_field($data['choir'] ?? ''),
    ];

    $response = wp_remote_post(LCHS_MEMBERS_API_URL, [
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-Key'    => LCHS_MEMBERS_API_KEY,
            'Accept'       => 'application/json',
        ],
        'body'    => json_encode($member_data),
    ]);

    if (is_wp_error($response)) {
        error_log('LCHS Members API Error: ' . $response->get_error_message());
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!($body['success'] ?? false)) {
            error_log('LCHS Members API Failed: ' . print_r($body, true));
        }
    }
}

