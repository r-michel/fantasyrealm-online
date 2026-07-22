# FantasyRealm Online

FantasyRealm Online est une application web permettant de créer, personnaliser et partager des personnages issus d'un univers fantasy.

---

# Stack technique

- PHP 8.4
- Symfony 7
- Twig
- Doctrine ORM
- Doctrine MongoDB ODM
- MySQL 8
- MongoDB
- Bootstrap 5
- Docker
- Mailpit

---

# Prérequis

Les outils suivants doivent être installés :

- Git
- Docker
- Docker Compose

---

# Installation

## 1. Cloner le dépôt

```bash
git clone https://github.com/r-michel/fantasyrealm-online.git
cd fantasyrealm-online
```

---

## 2. Démarrer les conteneurs

```bash
docker compose up -d --build
```

Vérifier que les conteneurs sont démarrés :

```bash
docker compose ps
```

---

## 3. Installer les dépendances

```bash
docker compose exec php composer install
```

---

## 4. Créer la base de données

```bash
docker compose exec php php bin/console doctrine:database:create --if-not-exists
```

---

## 5. Exécuter les migrations

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

MongoDB ne nécessite aucune migration. Les collections sont créées automatiquement lors du premier enregistrement.

---

## 6. Charger le catalogue d'équipements

```bash
docker compose exec php php bin/console doctrine:fixtures:load --group=equipment --append --no-interaction
```

Cette commande importe les catégories ainsi que tous les équipements disponibles dans l'application.

---

## 7. Créer le compte administrateur

Les informations du compte administrateur sont définies dans le fichier `.env.local` :

```dotenv
ADMIN_EMAIL=admin@example.com
ADMIN_USERNAME=Administrateur
ADMIN_PASSWORD=change-me
```

Une fois ces informations configurées, exécuter :

```bash
docker compose exec php php bin/console doctrine:fixtures:load --group=admin --append --no-interaction
```

---

## 8. Vider le cache Symfony

```bash
docker compose exec php php bin/console cache:clear
```

---

## 9. Accéder à l'application

Par défaut :

```
http://fantasyrealm-online.local
```

Si nécessaire, ajouter l'entrée suivante dans le fichier `/etc/hosts` :

```
127.0.0.1 fantasyrealm-online.local
```

---

# Mailpit

L'environnement de développement utilise **Mailpit** pour intercepter tous les emails envoyés par l'application.

Après avoir démarré les conteneurs Docker, l'interface est accessible à l'adresse suivante :

```
http://localhost:8025
```

Tous les emails générés par l'application y sont consultables, notamment :

- formulaire de contact ;
- réinitialisation du mot de passe ;
- validation ou rejet d'un personnage ;
- validation d'un commentaire ;
- toutes les notifications envoyées par l'application.

Aucun email n'est envoyé à de véritables destinataires en environnement de développement.

---

# Base de données

Le projet utilise deux bases de données.

## MySQL

Contient toutes les données métier :

- utilisateurs
- personnages
- équipements
- catégories
- commentaires
- relations entre les entités

## MongoDB

Contient les données documentaires :

- journaux d'activité
- paramètres dynamiques de l'application (bandeau d'annonce)

---

# Variables d'environnement principales

```
APP_ENV=dev

DATABASE_URL=mysql://fantasyrealm_user:fantasyrealm_pass@db:3306/fantasyrealm

MONGODB_URI=mongodb://root:root@mongodb:27017/?authSource=admin

MONGODB_DB=fantasyrealm_logs

MAILER_DSN=smtp://mailpit:1025
```

---

# Commandes utiles

## Arrêter les conteneurs

```bash
docker compose down
```

## Redémarrer les conteneurs

```bash
docker compose restart
```

## Voir les logs Docker

```bash
docker compose logs -f
```

## Ouvrir un shell dans le conteneur PHP

```bash
docker compose exec php bash
```

## Vider le cache

```bash
docker compose exec php php bin/console cache:clear
```

## Rejouer les migrations

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

## Recharger les équipements

```bash
docker compose exec php php bin/console doctrine:fixtures:load --group=equipment --append --no-interaction
```

## Recréer le compte administrateur

```bash
docker compose exec php php bin/console doctrine:fixtures:load --group=admin --append --no-interaction
```

---

# Développement

Le projet suit une architecture Symfony basée sur :

- Doctrine ORM pour les données relationnelles
- Doctrine MongoDB ODM pour les documents
- Twig pour le rendu serveur
- Bootstrap 5 pour l'interface
- Docker pour l'environnement de développement

---

# Auteur

Développé par **Rémi Michel**.