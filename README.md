<img src="assets/images/logo.png" alt="CritiPixel" width="200" />

# CritiPixel

## Pré-requis
* PHP >= 8.2
* Composer
* Extension PHP Xdebug
* Symfony (binaire)

## Installation

### Composer
Dans un premier temps, installer les dépendances :
```bash
composer install
```

### Docker (optionnel)
Si vous souhaitez utiliser Docker Compose, il vous suffit de lancer la commande suivante :
```bash
docker compose up -d
```

## Configuration

### Base de données
Actuellement, le fichier `.env` est configuré pour la base de données PostgreSQL mise en place dans `docker-compose.yml`.
Cependant, vous pouvez créer un fichier `.env.local` si nécessaire pour configurer l'accès à la base de données.
Exemple :
```dotenv
DATABASE_URL=postgresql://postgres:postgres@127.0.0.1:5432/criti_pixel?serverVersion=16&charset=utf8
```

### PHP (optionnel)
Vous pouvez surcharger la configuration PHP en créant un fichier `php.local.ini`.

De même pour la version de PHP que vous pouvez spécifier dans un fichier `.php-version`.

## Usage

### Base de données

#### Supprimer la base de données
```bash
symfony console doctrine:database:drop --force --if-exists
```

#### Créer la base de données
```bash
symfony console doctrine:database:create
```

#### Exécuter les migrations
```bash
symfony console doctrine:migrations:migrate -n
```

#### Charger les fixtures
```bash
symfony console doctrine:fixtures:load -n --purge-with-truncate
```

*Note : Vous pouvez exécuter ces commandes avec l'option `--env=test` pour les exécuter dans l'environnement de test.*

### SASS

#### Compiler les fichiers SASS
```bash
symfony console sass:build
```
*Note : le fichier `.symfony.local.yaml` est configuré pour surveiller les fichiers SASS et les compiler automatiquement quand vous lancez le serveur web de Symfony.*

### Tests
```bash
symfony php bin/phpunit
```

*Note : Penser à charger les fixtures avant chaque éxécution des tests.*

### Analyse statique

#### PHPStan
```bash
vendor/bin/phpstan analyse --no-progress
```

#### PHP CS Fixer (vérification)
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

#### PHP CS Fixer (correction automatique)
```bash
vendor/bin/php-cs-fixer fix
```

## Intégration continue

Le projet utilise **GitHub Actions** pour l'intégration continue. La pipeline se déclenche automatiquement à chaque push ou pull request et exécute dans l'ordre :

1. Installation des dépendances Composer
2. Compilation des assets Sass
3. Création de la base de données de test et chargement des fixtures
4. Exécution des tests PHPUnit
5. Vérification du style de code avec PHP CS Fixer
6. Analyse statique avec PHPStan (niveau 6)

Le fichier de configuration est disponible dans `.github/workflows/ci.yml`.

### Serveur web
```bash
symfony serve
```