# Palette de Couleurs Harmonisée - Statuts de l'Application

## Vue d'ensemble

Cette palette de couleurs a été conçue pour offrir une expérience utilisateur cohérente et moderne à travers toute l'application. Elle utilise les couleurs Tailwind CSS avec des bordures subtiles pour un rendu professionnel.

## Palette de Couleurs

### 🟢 Statuts Positifs (Vert Émeraude)
- **Couleur**: `emerald-100` / `emerald-700` / `emerald-200`
- **Utilisation**: Statuts de succès, validation, achèvement
- **Statuts**: `validated`, `completed`, `paid`, `present`, `active`, `published`

### 🔴 Statuts Négatifs (Rouge)
- **Couleur**: `red-100` / `red-700` / `red-200`
- **Utilisation**: Erreurs, rejets, annulations, absences
- **Statuts**: `rejected`, `cancelled`, `absent`, `inactive`

### 🟡 Statuts d'Attente (Ambre)
- **Couleur**: `amber-100` / `amber-700` / `amber-200`
- **Utilisation**: En attente, soumis, en cours de traitement
- **Statuts**: `pending`, `submitted`, `pending_validation`, `replaced`

### 🔵 Statuts de Planification (Indigo)
- **Couleur**: `indigo-100` / `indigo-700` / `indigo-200`
- **Utilisation**: Planification, programmation
- **Statuts**: `scheduled`, `planned`

### 🟣 Statuts en Cours (Violet)
- **Couleur**: `purple-100` / `purple-700` / `purple-200`
- **Utilisation**: En cours d'exécution, traitement actif
- **Statuts**: `in_progress`, `processing`, `published`

### 🔷 Statuts de Travail (Bleu)
- **Couleur**: `blue-100` / `blue-700` / `blue-200`
- **Utilisation**: Terminé, finalisé
- **Statuts**: `completed`

### 🟠 Statuts d'Alerte (Orange)
- **Couleur**: `orange-100` / `orange-700` / `orange-200`
- **Utilisation**: Retards, avertissements
- **Statuts**: `late`

### 🔵 Statuts de Prévalidation (Cyan)
- **Couleur**: `cyan-100` / `cyan-700` / `cyan-200`
- **Utilisation**: Prévalidation, étape intermédiaire
- **Statuts**: `prevalidated`

### ⚫ Statuts Neutres (Slate)
- **Couleur**: `slate-100` / `slate-700` / `slate-200`
- **Utilisation**: Brouillons, statuts par défaut, inactif
- **Statuts**: `draft`, `expected`, `inactive`

## Structure des Classes CSS

Chaque statut utilise la structure suivante :
```css
bg-{color}-100 text-{color}-700 border border-{color}-200
```

- `bg-{color}-100` : Fond clair
- `text-{color}-700` : Texte foncé pour le contraste
- `border border-{color}-200` : Bordure subtile pour la définition

## Composants

### StatusBadge
Composant générique pour tous les types de statuts avec icônes optionnelles.

### MeetingStatusBadge
Composant spécialisé pour les statuts de réunions avec gestion des retards.

## Utilisation

```vue
<template>
  <!-- Badge simple -->
  <StatusBadge status="validated" />
  
  <!-- Badge sans icône -->
  <StatusBadge status="pending" :show-icon="false" />
  
  <!-- Badge de réunion avec retard -->
  <MeetingStatusBadge status="scheduled" :is-late="true" />
</template>
```

## Cohérence Mobile

Cette palette est également utilisée dans l'application mobile Flutter pour maintenir la cohérence visuelle entre les plateformes.

## Avantages

1. **Cohérence visuelle** : Même palette sur toute l'application
2. **Accessibilité** : Contraste suffisant pour la lisibilité
3. **Modernité** : Design épuré avec bordures subtiles
4. **Maintenabilité** : Centralisation dans un fichier de configuration
5. **Évolutivité** : Facile d'ajouter de nouveaux statuts 