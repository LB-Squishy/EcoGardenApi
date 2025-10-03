[![forthebadge](https://forthebadge.com/images/badges/powered-by-coffee.svg)](https://forthebadge.com)

# EcoGardenApi

## Contenu:

Une API REST développée avec Symfony pour la gestion d'un jardin écologique.

## 📋 Prérequis

Avant d'installer le projet, assurez-vous d'avoir :

-   **PHP 8.1** ou supérieur
-   **Composer** (gestionnaire de dépendances PHP)
-   **MySQL** ou **MariaDB**
-   **Git**
-   **OpenSSL** (pour la génération des clés JWT)

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/EcoGardenApi.git
cd EcoGardenApi
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

Copiez le fichier `.env` vers `.env.local` et configurez vos paramètres :

```bash
copy .env .env.local
```

Modifiez le fichier `.env.local` avec vos paramètres de base de données :

```env
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/EcoGardenApi?serverVersion=8.0&charset=utf8mb4"
```

### 4. Configuration de la base de données

Créez la base de données :

```bash
php bin/console doctrine:database:create --if-not-exists
```

Appliquez les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

Alimentez la base de donnée:

```bash
php bin/console doctrine:fixtures:load
```

### 5. Configuration JWT (Authentification)

Créez le dossier pour les clés JWT :

```bash
mkdir config/jwt
```

Générez les clés privée et publique :

```bash
# Clé privée (vous devrez saisir une passphrase)
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096

# Clé publique
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Ajoutez la passphrase dans votre fichier `.env.local` :

```env
JWT_PASSPHRASE=votre_passphrase_ici
```

### 6. Démarrer le serveur de développement

```bash
symfony server:start
```

## ✅ Vérification de l'installation

Votre API devrait maintenant être accessible à l'adresse : `http://localhost:8000`

Vous pouvez tester l'API en accédant aux endpoints disponibles via Postman.

## 🛠️ Commandes utiles pour le développement

```bash
# Créer une nouvelle entité
php bin/console make:entity

# Créer une migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Créer un contrôleur
php bin/console make:controller

# Vider le cache
php bin/console cache:clear
```
