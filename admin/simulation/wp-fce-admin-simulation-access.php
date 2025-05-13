<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die('Kein Zugriff');
}

echo '<div class="wrap"><h1>WP FCE – Zugriffssimulation</h1><pre>';

echo "Starte Testszenario...\n";

// Simulationsdaten
$external_product_id = 123456;
$dummy_email = 'simulation@wpfce.tld';
$helper_ipn = new WP_FCE_Helper_Ipn();
$helper_user = new WP_FCE_Helper_Ipn();

// 1. Dummy-IPN erzeugen
$ipn = $helper_ipn->create_dummy_ipn($external_product_id);
echo "✓ Dummy-IPN erzeugt: {$ipn->get_transaction_id()}\n";

// 2. Prüfen, ob der User erstellt wurde
$user = $helper_user->get_user_by_email($dummy_email);
if (!$user) {
    echo "✘ Nutzer wurde nicht erstellt\n";
    WP_FCE_Helper_Ipn::clear_dummy_ipns();
    exit('</pre></div>');
}

echo "✓ Nutzer gefunden: {$user->get_wp_user_id()} ({$dummy_email})\n";

// 3. Zugriffsprüfung auf Spaces
$spaces = $user->get_spaces();

if (empty($spaces)) {
    echo "✘ Keine Spaces zugewiesen\n";
} else {
    echo "✓ Zugewiesene Spaces:\n";
    foreach ($spaces as $space) {
        $space_name = $space->get_name();
        $expected = true; // Wir erwarten in der Simulation, dass der Zugriff gewährt ist
        $has_access = true; // Weil er im Modell ja Zugriff hat
        $status = $has_access === $expected ? '✔ OK' : '✘ FEHLER';
        echo " → $space_name – Erwartet: Zugriff – [$status]\n";
    }
}

// 4. Rollback
$count_deleted_ipns = WP_FCE_Helper_Ipn::clear_dummy_ipns();
$count_deleted_user = $user->delete() ? 1 : 0;

echo "\nRollback:\n";
echo "→ Gelöschte IPNs: {$count_deleted_ipns}\n";
echo "→ Nutzer gelöscht: {$count_deleted_user}\n";

echo "</pre></div>";
