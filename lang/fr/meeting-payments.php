<?php

return [
    'status' => [
        'draft' => 'Brouillon',
        'submitted' => 'Soumis',
        'validated' => 'Validé',
        'rejected' => 'Rejeté',
    ],
    'payment_status' => [
        'pending' => 'En attente',
        'validated' => 'Validé',
        'paid' => 'Payé',
    ],
    'titles' => [
        'lists' => 'Listes de paiement',
        'create' => 'Créer une liste de paiement',
        'edit' => 'Modifier la liste de paiement',
        'show' => 'Détails de la liste de paiement',
    ],
    'messages' => [
        'created' => 'Liste de paiement créée avec succès',
        'updated' => 'Liste de paiement mise à jour avec succès',
        'submitted' => 'Liste de paiement soumise pour validation',
        'validated' => 'Liste de paiement validée',
        'rejected' => 'Liste de paiement rejetée',
        'payment_validated' => 'Paiement validé avec succès',
    ],
    'errors' => [
        'not_completed' => 'La réunion doit être terminée pour créer une liste de paiement',
        'already_exists' => 'Une liste de paiement existe déjà pour cette réunion',
        'cannot_submit' => 'Cette liste ne peut pas être soumise',
        'cannot_validate' => 'Cette liste ne peut pas être validée',
        'cannot_reject' => 'Cette liste ne peut pas être rejetée',
        'no_rights' => 'Vous n\'avez pas les droits pour effectuer cette action',
    ],
]; 