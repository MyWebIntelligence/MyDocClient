# MyDocClient

## Documents

Les documents sont des fichiers textes au format Markdown.

## Meta-données Dublin Core

MyDoc Intelligence supporte les 15 éléments de base de **Dublin Core**.

Dublin Core est l'un des schémas de métadonnées les plus simples et les plus utilisés.
Développé à l'origine pour décrire les ressources Web, Dublin Core a été utilisé pour
décrire une variété de ressources physiques et numériques.

La norme Dublin Core contient des définitions de chaque élément de métadonnées - 
comme la norme de contenu natif - qui indiquent quels types d'informations doivent
être enregistrés, où et comment. De nombreux éléments de données sont associés à 
des normes de valeur de données telles que le vocabulaire de type DCMI et les codes
de langue ISO 639, etc. De plus amples informations sont disponibles sur le site [Dublin Core Metadata Initiative](https://dublincore.org/).

### Élements de base Dublin Core

| Élement | Usage | Valeurs standard possibles |
| --- | --- | --- |
| **Title** | A name given to the resource. ||
| **Subject** | The topic of the resource. | Library of Congress Subject Headings (LCSH) |
| **Description** | An account of the resource. ||
| **Creator** | An entity primarily responsible for making the resource. | Library of Congress Name Authority File (LCNAF) |
| **Publisher** | An entity responsible for making the resource available. ||
| **Contributor** | An entity responsible for making contributions to the resource. | Library of Congress Name Authority File (LCNAF) |
| **Date** | A point or period of time associated with an event in the lifecycle of the resource. | W3CDTF |
| **Type** | The nature or genre of the resource. | DCMI Type Vocabulary |
| **Format** | The file format, physical medium, or dimensions of the resource. | Internet Media Types (MIME) |
| **Identifier** | An unambiguous reference to the resource within a given context. ||
| **Source** | A related resource from which the described resource is derived. ||
| **Language** | A language of the resource. | ISO 639 |
| **Relation** | A related resource. ||
| **Coverage** | The spatial or temporal topic of the resource, the spatial applicability of the resource, or the jurisdiction under which the resource is relevant. | Thesaurus of Geographic Names (TGN) |
| **Rights** | Information about rights held in and over the resource. ||

## Installation

Description de l'installation sur un serveur web Apache 2 + PHP-FPM + MariaDB

### Pré-requis

- Apache 2
- PHP >= 7.4
- MariaDB (recommandé) ou MySQL
- Git
- Composer (gestionnaire des paquets PHP)
- Yarn (gestionnaire des paquets JS)
- Compte SendinBlue pour l'envoi des mails (créer une clé API, voir configuration de l'application)

### Apache 2

Exemple de configuration Apache 2. Cette configuration simple n'est pas sécurisée par le mode SSL, certaines fonctionnalités
nécessitant une connexion sécurisée peuvent être indisponibles comme la reconnexion automatique de session.

Il est donc conseillé de disposer d'un domaine sécurisé par SSL et d'adapter la configuration en conséquence.

```
<VirtualHost *:80>
    DocumentRoot /var/www/MyDocClient/public
    DirectoryIndex /index.php
    
    <Directory /var/www/MyDocClient/public>
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride None
        Require all granted
        
        FallbackResource /index.php
    </Directory>
    
    <FilesMatch \.php$>
    # 2.4.10+ can proxy to unix socket
    SetHandler "proxy:unix:/var/run/php/php7.4-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/mydoc.error.log
    CustomLog ${APACHE_LOG_DIR}/mydoc.access.log combined
</VirtualHost>
```

### Installation des sources et dépendances

Dans le répertoire web (en général `/var/www/` sous Linux), récupérer les sources sur Github :

```
/var/www$ git clone https://github.com/MyWebIntelligence/MyDocClient.git
```

Rentrer dans le répertoire récupéré et installer les dépendances de l'application.

Dépendances PHP :

```
/var/www/MyDocClient$ composer install
```

Dépendances JS :

```
/var/www/MyDocClient$ yarn install
```

Build des assets :

```
/var/www/MyDocClient$ yarn build
```

### Édition de la configuration

La configuration spécifique à l'environnement se fait au travers des fichiers `.env` à la racine de l'application.

Dans tous les environnements, les fichiers suivants sont chargés s'ils existent,
le suivant prenant le pas sur le précédent :

$APP_ENV remplacé par l'environnement (dev ou prod) :

* .env contient les valeurs par défaut des variables d'environnement nécessaires à l'application
* .env.local fichier non vérsionné avec remplacements locaux
* .env.$APP_ENV valeurs par défaut spécifiques à l'environnement versionné
* .env.$APP_ENV.local remplacements spécifiques à l'environnement non versionné

Les variables d'environnement réelles (configurée au niveau système ou serveur web) l'emportent sur les fichiers .env.

NE DÉFINISSEZ PAS DE SECRETS DE PRODUCTION DANS CE DOSSIER NI DANS AUCUN AUTRE DOSSIER VERSIONNÉ.

Exécutez `composer dump-env prod` pour compiler les fichiers .env pour une utilisation en production (nécessite symfony/flex >=1.2).

https://symfony.com/doc/current/best_practices.html#use-environment-variables-for-infrastructure-configuration

Par exemple, pour définir votre environnement de production, créez le fichier `.env.prod` et éditez les paramètres de
connexion à la base de données (vérifiez la version de votre serveur de base de données) et la clé API de votre compte SendinBlue.


```
APP_ENV=prod
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my-doc?charset=utf8mb4&serverVersion=mariadb-10.x.x"
MAILER_DSN=sendinblue+api://KEY@default
```

### Base de données

Création de la base de données

```
/var/www/MyDocClient$ php bin/console doctrine:database:create
```