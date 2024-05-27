<?php
global $wpdb;

// Sécuriser la récupération des types de compte en utilisant des requêtes préparées
$account_types_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}account_types");
$account_types = $wpdb->get_results($account_types_query);

// Vérifier si un filtre a été sélectionné en utilisant filter_input pour nettoyer les données entrantes
$filter = filter_input(INPUT_GET, 'filter', FILTER_VALIDATE_INT);
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';

// Construire la requête en fonction du filtre sélectionné
$query = "SELECT a.*, t.name AS type_name 
          FROM {$wpdb->prefix}powerapi_accounts a 
          INNER JOIN {$wpdb->prefix}account_types t ON a.account_type_id = t.id";

if (!empty($filter)) {
    // Utiliser des requêtes préparées pour éviter les injections SQL
    $query .= $wpdb->prepare(" WHERE a.account_type_id = %d", $filter);
}

$query .= " ORDER BY a.created_at $sort_order";

$accounts = $wpdb->get_results($query);
?>

<div class="wrap">
    <h1>Comptes PowerAPI créés depuis ma-presence.online</h1>
    <p>Voici la liste des comptes PowerAPI créés depuis le site ma-presence.online.</p>
    <div>
        <label for="filter">Filtrer par type de compte :</label>
        <select name="filter" id="filter" onchange="filterAccounts()">
            <option value="">Tous les comptes</option>
            <?php foreach ($account_types as $type) : ?>
            <option value="<?php echo esc_attr($type->id); ?>" <?php selected($filter, $type->id); ?>>
                <?php echo esc_html($type->name); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="sort">Trier par date de création :</label>
        <select name="sort" id="sort" onchange="sortAccounts()">
            <option value="desc">Décroissant</option>
            <option value="asc" <?php selected($sort_order, 'ASC'); ?>>Croissant</option>
        </select>
    </div>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Adresse E-mail</th>
                <th>Type de Compte</th>
                <th>Statut</th>
                <th>Date de Création</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?php echo esc_html($account->id); ?></td>
                <td><?php echo esc_html($account->first_name); ?></td>
                <td><?php echo esc_html($account->last_name); ?></td>
                <td><?php echo esc_html($account->email); ?></td>
                <td><?php echo esc_html($account->type_name); ?></td>
                <td class="<?php echo $account->active ? 'status-active' : 'status-inactive'; ?>">
                    <div class="label">
                        <?php echo $account->active ? 'Actif' : 'Inactif'; ?>
                    </div>
                </td>
                <td><?php echo esc_html($account->created_at); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function filterAccounts() {
    var filter = document.getElementById('filter').value;
    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=powerapi_accounts_list')); ?>&filter=' +
        encodeURIComponent(filter);
}

function sortAccounts() {
    var sort = document.getElementById('sort').value;
    var currentUrl = '<?php echo esc_url(admin_url('admin.php?page=powerapi_accounts_list')); ?>';
    var filter = '<?php echo $filter ? "&filter=$filter" : ""; ?>';
    window.location.href = currentUrl + '&sort=' + encodeURIComponent(sort) + filter;
}
</script>

<style>
.status-active .label {
    background-color: #c6e1c6;
    color: #5b841b !important;
}

.status-inactive .label {
    background-color: #eba3a3;
    color: #761919 !important;
}

.label {
    padding: 5px 10px;
    border-radius: 5px;
    width: fit-content;
}
</style>
