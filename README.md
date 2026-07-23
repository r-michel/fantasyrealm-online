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

D'abord copier le `.env.example` à la racine et le renommer en `.env`, puis compléter les mots de passe MySQL et MongoDB (utiliser par exemple la commande `openssl rand -hex 16` pour générer les mots de passe).

Puis lancer le build et les conteneurs :

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
docker compose exec --user www-data php composer install
```

---

## 4. Créer la base de données

```bash
docker compose exec --user www-data php php bin/console doctrine:database:create --if-not-exists
```

---

## 5. Exécuter les migrations

```bash
docker compose exec --user www-data php php bin/console doctrine:migrations:migrate --no-interaction
```

MongoDB ne nécessite aucune migration. Les collections sont créées automatiquement lors du premier enregistrement.

---

## 6. Charger le catalogue d'équipements

```bash
docker compose exec --user www-data php php bin/console doctrine:fixtures:load --group=equipment --append --no-interaction
```

Cette commande importe les catégories ainsi que tous les équipements disponibles dans l'application.

---

## 7. Créer le compte administrateur

Les informations du compte administrateur sont définies dans le fichier `.env.local` à créer dans `app/` :

```dotenv
ADMIN_EMAIL=admin@example.com
ADMIN_USERNAME=Administrateur
ADMIN_PASSWORD=change-me
```

Une fois ces informations configurées, exécuter :

```bash
docker compose exec --user www-data php php bin/console doctrine:fixtures:load --group=admin --append --no-interaction
```

---

## 8. Vider le cache Symfony

```bash
docker compose exec --user www-data php php bin/console cache:clear
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

En production les emails sont envoyés par Brevo.

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

# Variables d'environnement

Les principales variables d'environnement sont réparties entre :

## Racine du projet (.env)
- configuration Docker Compose
- mots de passe des services
- ports exposés

## Application Symfony (app/.env.local ou .env.prod.local)

```
APP_ENV
APP_SECRET
DATABASE_URL
MONGODB_URI
MONGODB_DB
MAILER_DSN
ADMIN_EMAIL
ADMIN_USERNAME
ADMIN_PASSWORD
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
docker compose exec --user www-data php bash
```

## Vider le cache

```bash
docker compose exec --user www-data php php bin/console cache:clear
```

## Rejouer les migrations

```bash
docker compose exec --user www-data php php bin/console doctrine:migrations:migrate
```

## Recharger les équipements

```bash
docker compose exec --user www-data php php bin/console doctrine:fixtures:load --group=equipment --append --no-interaction
```

## Recréer le compte administrateur

```bash
docker compose exec --user www-data php php bin/console doctrine:fixtures:load --group=admin --append --no-interaction
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

## Résolution des problèmes de permissions

Symfony doit pouvoir écrire dans le dossier `var/`, utilisé notamment pour le cache, les journaux, les proxies et les hydrateurs Doctrine MongoDB ODM.

Les droits de ce dossier sont normalement configurés automatiquement au démarrage du conteneur PHP.

Les commandes Composer et Symfony doivent être exécutées avec l'utilisateur `www-data` :

```bash
docker compose exec --user www-data php composer install
docker compose exec --user www-data php php bin/console cache:clear
```

Si une erreur telle que la suivante apparaît :

```text
Your hydrator directory must be writable
```

il est possible de réinitialiser le propriétaire du dossier `var/` avec :

```bash
docker compose exec php chown -R www-data:www-data var
docker compose exec --user www-data php php bin/console cache:clear
```

La commande `chmod -R 777 var` peut résoudre temporairement le problème, mais elle n'est pas recommandée en raison des permissions trop permissives qu'elle applique.

---

# Auteur

Développé par **Rémi Michel**.