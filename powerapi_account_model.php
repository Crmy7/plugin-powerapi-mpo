<?php
class PowerAPI_Account_Model {
    public function insert_account($first_name, $last_name, $email, $account_type_id) {
        global $wpdb;
    
        // Utiliser le préfixe spécifique à votre installation
        $table_name = 'wp_946105_powerapi_accounts';
    
        // Vérifier si l'adresse e-mail existe déjà dans la table
        $existing_email_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email = %s", $email));
    
        if ($existing_email_check == 0) {
            // L'adresse e-mail n'existe pas, insérer le nouvel utilisateur
            $wpdb->insert(
                $table_name,
                array(
                    'first_name' => sanitize_text_field($first_name),
                    'last_name' => sanitize_text_field($last_name),
                    'email' => sanitize_email($email),
                    'account_type_id' => $account_type_id, // Ajouter le type de compte Associé
                    'active' => true // Ajoutez une valeur par défaut pour le statut
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%d', // Ajouter le placeholder pour account_type_id
                    '%s' // Ajoutez le placeholder pour le statut
                )
            );
        } else {
            // L'adresse e-mail existe déjà, ne rien faire ou ajouter un message d'erreur personnalisé
            error_log('Email already exists in ' . $table_name . ': ' . $email);
        }
    }    
}

// Ajoutez ce code à votre fichier de fonctions ou à votre plugin
add_action('woocommerce_subscription_status_cancelled', 'update_powerapi_account_status', 10, 1);

function update_powerapi_account_status($subscription_id) {
    // Récupérer l'adresse e-mail associée à cet abonnement
    $customer_email = get_post_meta($subscription_id, '_billing_email', true);

    if ($customer_email) {
        global $wpdb;
        // Utiliser le préfixe spécifique à votre installation
        $table_name = 'wp_946105_powerapi_accounts';

        // Mettre à jour le statut du compte dans la table powerapi_accounts
        $wpdb->update(
            $table_name,
            array('active' => 'false'),
            array('email' => $customer_email),
            array('%s'),
            array('%s')
        );
    }
}
?>