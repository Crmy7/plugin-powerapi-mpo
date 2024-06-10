<?php
class PowerAPIAccountModel {
    public function insert_account($first_name, $last_name, $email, $account_type_id) {
        global $wpdb;
    
        // Utilisation de requêtes préparées pour éviter les attaques par injection SQL
        $table_name = $wpdb->prefix . 'powerapi_accounts';
        $existing_email_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email = %s", $email));
    
        if ($existing_email_check == 0) {
            // Si l'adresse e-mail n'existe pas, insérer le nouvel utilisateur
            $wpdb->insert(
                $table_name,
                array(
                    'first_name' => sanitize_text_field($first_name),
                    'last_name' => sanitize_text_field($last_name),
                    'email' => sanitize_email($email),
                    'account_type_id' => absint($account_type_id),
                    'active' => true 
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%d', 
                    '%s'
                )
            );
        } else {
            error_log('Email already exists in ' . $table_name . ': ' . $email);
        } 
        if ($wpdb->last_error) {
            error_log('Error inserting account: ' . $wpdb->last_error);
        }
    }    
}

function updatePowerapiAccountStatus($subscription_id) {
    // Récupérer l'adresse e-mail associée à cet abonnement
    $customer_email = get_post_meta($subscription_id, '_billing_email', true);

    if ($customer_email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'powerapi_accounts';

        // Mettre à jour le statut du compte dans la table powerapi_accounts
        $wpdb->update(
            $table_name,
            array('active' => 'false'),
            array('email' => sanitize_email($customer_email)),
            array('%s'),
            array('%s')
        );
    }
}
