<?php

// Require du modèle PowerAPI_Account_Model
require_once plugin_dir_path(__FILE__) . 'powerapi_account_model.php';
// Déterminer le chemin vers la racine de l'installation WordPress
$rootPath = dirname(dirname(dirname(dirname(__FILE__))));

// Inclure l'autoloader de Composer
require $rootPath . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->load();

class PowerAPI_Controller
{
    function handle_powerapi_login()
    {
        // Check if the request has a valid nonce (security measure)
        check_ajax_referer('powerapi_login_nonce', 'security');

        // Check if the reCAPTCHA response is present in the POST data
        if (isset($_POST['g-recaptcha-response'])) {
            // Get the reCAPTCHA response
            $recaptchaResponse = sanitize_text_field($_POST['g-recaptcha-response']);

            // Verify the reCAPTCHA response
            $recaptchaSecretKey = $_ENV['RECAPTCHA_SECRET_KEY'];
            $verificationUrl = "https://www.google.com/recaptcha/api/siteverify";
            $data = [
                'secret' => $recaptchaSecretKey,
                'response' => $recaptchaResponse,
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $verificationResult = file_get_contents($verificationUrl, false, $context);
            $verificationData = json_decode($verificationResult);

            if ($verificationData->success !== true) {
                // reCAPTCHA verification failed
                echo json_encode(array('status' => 'error', 'message' => 'La vérification reCAPTCHA a échoué.'));
                exit;
            }
        } else {
            // reCAPTCHA response not found in POST data
            echo json_encode(array('status' => 'error', 'message' => 'Veuillez compléter la vérification reCAPTCHA.'));
            exit;
        }

        if (isset($_POST['email']) && isset($_POST['password'])) {
            // Get the submitted email and password
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Make the POST request to the PowerAPI server
            $url = 'https://api.powerapi.com/oauth/token';
            $headers = [
                'Content-Type: application/vnd.api+json',
                'Accept: application/vnd.api+json',
            ];
            $data = [
                'scope' => 'trusted public',
                'grant_type' => 'password',
                'email' => $email,
                'password' => $password,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            // Set cURL option to verify SSL certificates
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode === 200) {
                // Token retrieved successfully, return the token as the response
                $token = json_decode($response, true)['access_token'];
                echo json_encode(array('status' => 'success', 'token' => $token));
            } else {
                // Handle the error, return an error message as the response
                echo json_encode(array('status' => 'error', 'message' => 'La connexion a échoué. Veuillez vérifier vos informations de connexion.'));
            }
        } else {
            // Handle the error, return an error message as the response
            echo json_encode(array('status' => 'error', 'message' => 'Demande non valide. Veuillez fournir une adresse électronique et un mot de passe valide.'));
        }
        exit; // Terminate the script after handling the AJAX request
    }

    function generateRandomPassword()
    {
        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
        $passwordLength = 12;
        $password = "";

        for ($i = 0; $i < $passwordLength; $i++) {
            $randomIndex = mt_rand(0, strlen($characters) - 1);
            $password .= $characters[$randomIndex];
        }

        return $password;
    }

    function send_user_details()
    {
        $customer_email = sanitize_email($_POST['customer_email']); // Récupérer l'adresse e-mail
        $user_name = $customer_email;
        $password = sanitize_text_field($_POST['password']); // Récupérer le mot de passe

        // Adresse e-mail de l'expéditeur
        $from_email = $_ENV['SUPPORT_EMAIL'];
        $from_name = $_ENV['SUPPORT_NAME'];

        // Créer un objet e-mail pour le client
        $subject = 'Votre compte ma-presence.online a été créé';
        $message = "
        Bonjour,
        <br><br>
        Votre compte ma-presence.online a été créé avec succès.
        <br><br>
        Vos identifiants de connexion sont les suivants :
        <br><br>
        * Nom d'utilisateur : $user_name
        <br>
        * Mot de passe : $password
        <br><br>
        Pour vous connecter, rendez-vous sur la page suivante :
        <br><br>
        <a href='https://ma-presence.online' style='color: #F15A24;'>https://ma-presence.online</a>
        <br><br>
        Avec nos meilleures salutations,
        <br><br>
        <table border='0' cellpadding='0' width='450' style='table-layout:fixed;text-size-adjust: none !important; -ms-text-size-adjust: none !important; -webkit-text-size-adjust: none !important;'> <tbody> <tr> <td align='left' valign='top' width='68'> <span style='margin: 0px 10px 0px 0px; padding: 0px; display: block; font-family: Helvetica, Arial, sans-serif;'> <a style='text-decoration:none' href='https://htmlsig.com/t/091fa30e16c78ff2'> <img src='https://htmlsigs.s3.amazonaws.com/logos/files/001/373/813/landscape/Capture_d_e%CC%81cran_2023-08-14_a%CC%80_09.04.27.png' alt='ma-présence.online' border='0' height='80' width='68'> </a> <p style='margin:0; padding:0; line-height:10px; display:block;width:100%; font-size:1;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='58' height='10' style='display: block; width: 100%; height: 6px;'>
            </p> </span> </td> <td align='left' valign='top' nowrap='nowrap' width='10'> <span style='margin: 0px; padding: 0px; line-height: 100%; font-family: Helvetica, Arial, sans-serif;'><img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='10'></span> </td> <td align='left' nowrap='nowrap' valign='top' width='372'> <span style='margin: 0px; padding: 0px; font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 14.4px; color: rgb(33, 33, 33); display: block;'> <span style='font-weight: bold; font-size: 1.4em; line-height: 1.2em; font-family: Helvetica, Arial, sans-serif; display: inline; color: rgb(33, 33, 33);'>Support</span> <span style='display: inline; font-family: Helvetica, Arial, sans-serif;'> | </span> <span style='color: rgb(33, 33, 33); font-size: 1.2em; font-family: Helvetica, Arial, sans-serif; display: inline;'>ma-presence.online</span> </span> <span style='margin: 0px; padding: 0px; font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 14.4px; color: rgb(33, 33, 33); display: block;'> <a href='mailto:support@ma-presence.online' style='color: rgb(224, 112, 8); text-decoration: none !important; display: inline;'>support@ma-presence.online</a><span style='color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif;'></span> </span> <p style='margin:0; padding:0; line-height:10px; display:block;width:100%; font-size:1;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='372' height='10' style='display: block; width: 100%; height: 6px;'>
            </p> <span style='margin: 0px 20px 0px 0px; padding: 0px; text-align: left; font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 14.4px; display: block;'> <span style='font-weight: bold; color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif; display: inline;'>ma-présence.online</span> <span style='display: inline; font-family: Helvetica, Arial, sans-serif;'><br></span> <span style='color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif;'></span> <span style='color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif;'></span> <span style='font-family: Helvetica, Arial, sans-serif;'></span> <span style='color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif; display: inline;'>Chemin Louis-Hubert 2</span> <span style='display: inline; font-family: Helvetica, Arial, sans-serif;'><br></span> <span style='color: rgb(33, 33, 33); font-family: Helvetica, Arial, sans-serif; display: inline;'>1213 Petit-Lancy</span> <span style='display: inline; font-family: Helvetica, Arial, sans-serif;'><br></span> <a href='https://ma-presence.online/' style='color: rgb(224, 112, 8); text-decoration: none !important; display: inline;'>https://ma-presence.online/</a> <p style='margin:0; padding:0; line-height:10px; display:block;width:100%; font-size:1;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='352' height='10' style='display: block; width: 100%; height: 6px;'>
            </p> </span> <div style='margin:0; padding:0; margin-right:20px;'> <span style='margin: 0px; padding: 0px; line-height: 100%; font-size: 1px; display: block; font-family: Helvetica, Arial, sans-serif;'> <a style='text-decoration: none; display: inline;' href='https://htmlsig.com/t/579181a43dd59b57'><img width='16' style='margin-bottom:2px; border:none; display:inline;' height='16' data-filename='facebook.png' src='https://s3.amazonaws.com/htmlsig-assets/grey/facebook.png' alt='Facebook'></a> <span style='white-space: nowrap; font-family: Helvetica, Arial, sans-serif; display: inline;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='2'> </span> <a style='display: inline; text-decoration: none;' href='https://htmlsig.com/t/977d184ce2a6cc6b'><img width='16' style='margin-bottom:2px; border:none; display:inline;' height='16' data-filename='maps.png' src='https://s3.amazonaws.com/htmlsig-assets/grey/maps.png' alt='Maps'></a> <span style='white-space: nowrap; font-family: Helvetica, Arial, sans-serif; display: inline;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='2'> </span> </span>
            <p style='margin:0; padding:0; line-height:10px; display:block;width:100%; font-size:1;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='352' height='10' style='display: block; width: 100%; height: 6px;'>
            </p> </div> </td> </tr> <tr> <td colspan='3'> <p style='margin:0; padding:0; line-height:10px; display:block;width:100%; font-size:1;'> <img src='https://s3.amazonaws.com/htmlsig-assets/spacer.gif' width='450' height='10' style='display: block; width: 100%; height: 6px;'>
            </p> <span style='margin:0; padding:0; font-family: Helvetica, Arial, sans-serif; color: #212121; font-size: 9px; line-height: 12px; display:block;'></span> </td> </tr> </tbody> </table>
            
    ";

        // En-têtes de l'e-mail
        $headers = "From: $from_name <$from_email>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Envoyer l'e-mail au client
        wp_mail($customer_email, $subject, $message, $headers);

        // Envoyer une copie de l'e-mail à l'adresse de support
        $support_email = getenv('SUPPORT_EMAIL'); // Adresse de support
        $support_subject = 'Nouveau compte créé sur ma-presence.online';

        // Le message est le même que celui envoyé au client, vous pouvez le personnaliser si nécessaire
        $support_message = $message;

        // En-têtes pour l'e-mail de support
        $support_headers = "From: $from_name <$from_email>\r\n";
        $support_headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Envoyer l'e-mail à l'adresse de support
        wp_mail($support_email, $support_subject, $support_message, $support_headers);
    }

    // Gestion plus détaillée des erreurs avec journalisation
    function createUserAccount($first_name, $last_name, $billing_email, $access_token, $account_type_id)
    {
        try {
            // Adresse e-mail et mot de passe pour le compte utilisateur à créer
            $customer_email = sanitize_email($billing_email);
            $password = generateRandomPassword();

            // URL de l'API pour créer le compte utilisateur
            $userUrl = "https://api.powerapi.com/api/v1/users";
            $userHeaders = array(
                "Content-Type: application/vnd.api+json",
                "Accept: application/vnd.api+json",
                "Authorization: Bearer " . $access_token
            );
            $userData = array(
                "data" => array(
                    "type" => "users",
                    "attributes" => array(
                        "email" => $customer_email,
                        "password" => $password,
                        "password_confirmation" => $password,
                        "source" => "partner", // Utilisez une valeur valide reconnue par l'API
                        "language" => "fr",
                        "terms_agreement" => true
                    )
                )
            );

            // Initialiser cURL pour créer le compte utilisateur
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $userUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $userHeaders);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Exécution de la requête cURL pour créer le compte utilisateur
            $response = curl_exec($ch);

            // Vérifier les erreurs de cURL
            if (curl_errno($ch)) {
                throw new Exception('Erreur cURL : ' . curl_error($ch));
            } else {
                // Réussite, récupérer les informations et envoyer l'e-mail
                $email_response = json_decode($response, true);
                $email_data = array(
                    'customer_email' => $customer_email,
                    'password' => $password
                );

                // Envoyer les informations par requête POST à l'action WordPress
                $send_user_details_url = admin_url('admin-ajax.php?action=send_user_details');
                $email_request = curl_init($send_user_details_url);
                curl_setopt($email_request, CURLOPT_POST, true);
                curl_setopt($email_request, CURLOPT_POSTFIELDS, $email_data);
                curl_setopt($email_request, CURLOPT_RETURNTRANSFER, true);
                $email_response = curl_exec($email_request);

                // Vérifier les erreurs de cURL lors de l'envoi de l'e-mail
                if (curl_errno($email_request)) {
                    throw new Exception('Erreur cURL lors de l\'envoi de l\'e-mail : ' . curl_error($email_request));
                } else {
                    // Enregistrer les informations du compte dans la table powerapi_accounts
                    $account_model = new PowerAPI_Account_Model();
                    $account_model->insert_account($first_name, $last_name, $customer_email, $account_type_id);
                }

                // Fermeture de la session cURL pour l'envoi d'e-mail
                curl_close($email_request);
            }

            // Fermeture de la session cURL pour la création du compte utilisateur
            curl_close($ch);
        } catch (Exception $e) {
            // Journalisation des erreurs
            error_log('Erreur lors de la création du compte utilisateur : ' . $e->getMessage());
            // Ne pas afficher les erreurs au front
        }
    }

    // Utilisation de blocs try-catch pour gérer les exceptions
    function log_billing_email_on_order_completion($order_id)
    {
        try {
            // Récupérer la commande
            $order = wc_get_order($order_id);

            // Récupérer les ID de produit de la commande
            $product_ids = array();
            foreach ($order->get_items() as $item) {
                $product_ids[] = $item->get_product_id();
            }

            // Tableau de mappage entre les ID de produit et les ID de type de compte
            $product_account_type_map = array(
                28971 => 1,
                29234 => 1,
                29255 => 2,
                29075 => 2,
            );

            // ID de produit cible
            $target_product_ids = array_keys($product_account_type_map);

            // Vérifier si l'un des ID de produit dans la commande correspond aux ID de produit cibles
            $matching_product_ids = array_intersect($product_ids, $target_product_ids);

            // Si des ID de produit correspondants sont trouvés, exécutez le script
            if (!empty($matching_product_ids)) {
                // Obtenir l'adresse e-mail de facturation
                $billing_email = $order->get_billing_email();
                $first_name = $order->get_billing_first_name();
                $last_name = $order->get_billing_last_name();

                // Obtenir l'ID du type de compte en fonction du produit commandé
                $account_type_id = $product_account_type_map[$matching_product_ids[0]]; // Prendre le premier ID de produit correspondant

                // Obtenez le token d'accès en utilisant la requête cURL
                $token_request_url = 'https://api.powerapi.com/oauth/token';
                $token_request_data = array(
                    "scope" => "trusted public",
                    "grant_type" => "password",
                    "email" => $_ENV['POWERAPI_EMAIL'],
                    "password" => $_ENV['POWERAPI_PASSWORD']
                );
                $token_headers = array(
                    'Content-Type: application/json',
                    'Accept: application/vnd.api+json'
                );

                // Initialiser cURL pour obtenir le token
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $token_request_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($token_request_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $token_headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Exécution de la requête cURL pour obtenir le token
                $token_response = curl_exec($ch);

                // Vérifier les erreurs de cURL
                if (curl_errno($ch)) {
                    throw new Exception('Erreur cURL : ' . curl_error($ch));
                } else {
                    // Obtenir le token d'accès à partir de la réponse
                    $token_data = json_decode($token_response, true);
                    $access_token = $token_data['access_token'];

                    // Créer le compte utilisateur en utilisant le token d'accès et l'ID du type de compte
                    createUserAccount($first_name, $last_name, $billing_email, $access_token, $account_type_id);
                }

                // Fermeture de la session cURL pour la récupération du token
                curl_close($ch);
            }
        } catch (Exception $e) {
            // Journalisation des erreurs
            error_log('Erreur lors de la récupération du token d\'accès ou de la création du compte utilisateur : ' . $e->getMessage());
            // Ne pas afficher les erreurs au front
        }
    }
}