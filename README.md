# MyDoc: An Innovative Platform for Collaborative Corpus Annotation

MyDoc is an innovative platform designed to meet the needs of researchers, practitioners, and professionals working with complex corpora. By combining collaboration, customization, and advanced analysis, MyDoc transforms how corpora are studied and annotated, making the process more intuitive, dynamic, and collaborative.

https://www.youtube.com/watch?v=772E1gq5A8o

## ✨ A Vision for Research and Document Analysis

The MyDoc project was born out of the need to simplify corpus annotation and analysis in an environment where online collaboration and digital tools are essential. The platform is built on a strong idea: to allow geographically dispersed or interdisciplinary teams to work together smoothly and effectively on various types of corpora.

Whether it's annotating a literary text, analyzing a political speech, or structuring scientific data, MyDoc adapts to different needs thanks to its intuitive interface and powerful tools.

## 🌟 Why Is MyDoc Unique?

**1. Collaboration at the Heart of the User Experience**  
MyDoc is designed as a collective workspace. You can share documents, collaborate in real-time on annotations, and open discussions directly on the platform. Each project becomes a true digital laboratory where a team cleans, annotates, and categorizes document passages for subsequent analysis.

**2. Tools Tailored to Your Specific Needs**  
With MyDoc, you don't have to adapt to the tool—it adapts to your projects. The platform allows for the creation of customized annotations, flexible corpus structuring, and data exploration from various perspectives through an efficient search engine.

**3. An Intuitive Interface for All Users**  
No need to be a technical expert to use MyDoc. The user interface is designed to be accessible to beginners while offering advanced features for experienced users.

**4. Annotation Export for NLP, LLM and co**

The goal of MyDoc is not only to provide a centralized collaboration tool for researchers to manage and annotate corpora collectively, but also to enable fine-tuned exports of annotations according to specific parameters and stages of analysis.

MyDoc is also an indispensable tool for building annotation corpora for natural language processing (NLP) tasks or fine-tuning large language models.
## 🚀 Key Features

### Collaborative Annotation

- Ability to work on projects as a team.
- Creation of customized metadata to structure annotations.
- Support for various types of annotations (textual, categorical, etc.).

### Corpus Management

- Import and export documents in standard formats.
- Organize corpora into folders or projects for optimized management.
- Advanced search within annotated corpora.

### User Interface

- **Simplified Navigation**: A clear interface providing quick access to projects, annotations, and documents.
- **Visual Annotations**: Highlighted annotated elements for better readability.

### Analysis and Visualization

- Generate analytical reports based on annotations.
- Visualize relationships within corpora using interactive graphs.
- Integration with textual statistics tools.

### Example Screenshots

# Galerie d'images

| Image 1                     | Image 2                     | Image 3                     | Image 4                     |
|------------------------------|------------------------------|------------------------------|------------------------------|
| ![Screen shoot 01](screen01.png) | ![Screen shoot 02](screen02.png) | ![Screen shoot 03](screen03.png) | ![Screen shoot 04](screen04.png) |

## 🤝 Contributors

- **Pr. Franck Cormerais**: Project Lead
- **Dr. Amar Lakel**: Development Lead
- **Jean Devalance**: Frontend and Backend Developer

## 📝 License

This project is licensed under the MIT License.

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
* .env.local fichier non versionné avec remplacements locaux
* .env.$APP_ENV valeurs par défaut spécifiques à l'environnement versionné
* .env.$APP_ENV.local remplacements spécifiques à l'environnement non versionné

Les variables d'environnement réelles (configurée au niveau système ou serveur web) l'emportent sur les fichiers .env.

NE DÉFINISSEZ PAS DE SECRETS DE PRODUCTION DANS CE FICHIER NI DANS AUCUN AUTRE FICHIER VERSIONNÉ.

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
