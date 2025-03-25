# Documentation de l'API Colocs

## Introduction

Cette documentation décrit l'API RESTful de l'application Colocs. L'API utilise l'authentification par token (Sanctum) et renvoie les données au format JSON.

## Base URL

```
https://api.meeting-lara.com/api
```

## Authentification

### Connexion

```http
POST /login
```

**Paramètres :**
- `email` (string, required) : Adresse email de l'utilisateur
- `password` (string, required) : Mot de passe de l'utilisateur

**Réponse :**
```json
{
    "token": "1|abcdef123456...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "roles": ["admin"]
    }
}
```

### Inscription

```http
POST /register
```

**Paramètres :**
- `name` (string, required) : Nom complet de l'utilisateur
- `email` (string, required) : Adresse email unique
- `password` (string, required) : Mot de passe (minimum 8 caractères)
- `password_confirmation` (string, required) : Confirmation du mot de passe

**Réponse :**
```json
{
    "token": "1|abcdef123456...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "roles": []
    }
}
```

### Déconnexion

```http
POST /logout
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "message": "Déconnexion réussie"
}
```

## Réunions

### Liste des réunions

```http
GET /meetings
```

**Headers :**
- `Authorization: Bearer {token}`

**Paramètres de requête :**
- `status` (string, optional) : Filtrer par statut
- `date_from` (date, optional) : Date de début
- `date_to` (date, optional) : Date de fin
- `local_committee_id` (integer, optional) : ID du comité local
- `per_page` (integer, optional) : Nombre d'éléments par page (défaut: 10)

**Réponse :**
```json
{
    "meetings": [
        {
            "id": 1,
            "title": "Réunion du comité",
            "scheduled_date": "2024-03-20 14:00:00",
            "status": "scheduled",
            "local_committee": {
                "id": 1,
                "name": "Comité A",
                "locality": {
                    "id": 1,
                    "name": "Ville A"
                }
            },
            "attendees": [...]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50
    }
}
```

### Détails d'une réunion

```http
GET /meetings/{meeting}
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "meeting": {
        "id": 1,
        "title": "Réunion du comité",
        "scheduled_date": "2024-03-20 14:00:00",
        "status": "scheduled",
        "local_committee": {...},
        "attendees": [...],
        "agenda": [...],
        "minutes": {...},
        "attachments": [...],
        "enrollmentRequests": [...]
    }
}
```

### Marquer la présence

```http
POST /meetings/{meeting}/attendance
```

**Headers :**
- `Authorization: Bearer {token}`

**Paramètres :**
- `attendee_id` (integer, required) : ID du participant
- `status` (string, required) : "present" ou "absent"
- `arrival_time` (datetime, required si status="present") : Heure d'arrivée
- `replacement_name` (string, required si status="absent") : Nom du remplaçant
- `replacement_phone` (string, optional) : Téléphone du remplaçant
- `replacement_role` (string, optional) : Rôle du remplaçant
- `comments` (string, optional) : Commentaires

**Réponse :**
```json
{
    "message": "Présence mise à jour avec succès",
    "attendee": {...}
}
```

### Finaliser la liste de présence

```http
POST /meetings/{meeting}/attendance/finalize
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "message": "Liste de présence finalisée avec succès"
}
```

## Participants

### Liste des participants

```http
GET /meetings/{meeting}/attendees
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "attendees": [
        {
            "id": 1,
            "name": "John Doe",
            "phone": "+1234567890",
            "role": "membre",
            "village": {
                "id": 1,
                "name": "Village A"
            },
            "is_expected": true,
            "is_present": true,
            "attendance_status": "present",
            "arrival_time": "2024-03-20 14:05:00",
            "comments": "En retard",
            "payment_status": "pending"
        }
    ]
}
```

### Marquer comme présent

```http
POST /attendees/{attendee}/present
```

**Headers :**
- `Authorization: Bearer {token}`

**Paramètres :**
- `arrival_time` (datetime, optional) : Heure d'arrivée

**Réponse :**
```json
{
    "message": "Participant marqué comme présent",
    "attendee": {...}
}
```

### Marquer comme absent

```http
POST /attendees/{attendee}/absent
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "message": "Participant marqué comme absent",
    "attendee": {...}
}
```

## Enrôlements

### Liste des demandes d'enrôlement

```http
GET /meetings/{meeting}/enrollments
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "requests": [
        {
            "id": 1,
            "first_name": "Jane",
            "last_name": "Doe",
            "phone": "+1234567890",
            "email": "jane@example.com",
            "address": "123 rue Example",
            "notes": "Notes importantes",
            "status": "pending"
        }
    ]
}
```

### Créer une demande d'enrôlement

```http
POST /meetings/{meeting}/enrollments
```

**Headers :**
- `Authorization: Bearer {token}`

**Paramètres :**
- `first_name` (string, required) : Prénom
- `last_name` (string, required) : Nom
- `phone` (string, optional) : Téléphone
- `email` (string, optional) : Email
- `address` (string, optional) : Adresse
- `notes` (string, optional) : Notes

**Réponse :**
```json
{
    "message": "Demande d'enrôlement créée avec succès",
    "request": {...}
}
```

## Données de référence

### Liste des sous-préfectures

```http
GET /sub-prefectures
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "sub_prefectures": [
        {
            "id": 1,
            "name": "Sous-préfecture A",
            "code": "SPA"
        }
    ]
}
```

### Villages d'une sous-préfecture

```http
GET /sub-prefectures/{subPrefectureId}/villages
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "villages": [
        {
            "id": 1,
            "name": "Village A",
            "code": "VLA"
        }
    ]
}
```

### Liste des comités locaux

```http
GET /local-committees
```

**Headers :**
- `Authorization: Bearer {token}`

**Réponse :**
```json
{
    "local_committees": [
        {
            "id": 1,
            "name": "Comité A",
            "locality": {
                "id": 1,
                "name": "Ville A"
            }
        }
    ]
}
```

## Codes d'erreur

- `401` : Non authentifié
- `403` : Non autorisé
- `404` : Ressource non trouvée
- `422` : Erreur de validation
- `500` : Erreur serveur

## Bonnes pratiques

1. Toujours inclure le header `Authorization: Bearer {token}` pour les requêtes authentifiées
2. Gérer les erreurs de validation (422) en affichant les messages appropriés
3. Implémenter une gestion de la pagination pour les listes
4. Mettre en cache les données de référence (sous-préfectures, villages, comités)
5. Gérer la déconnexion en supprimant le token local 