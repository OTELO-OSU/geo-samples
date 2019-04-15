# Installation  

**Prérequis :**

    -MongoDB 3.4.2

    -Elasticsearch 5.2

    -Mongo connector 

    -PHP 5.6

    -PHP-curl
    
    -PHP libssh2

    -MongoDB php driver
    
    -MYSQL


Pour debian 9 (pour d’autre systèmes consulter le manuel de mongodb)

**Installation de mongodb :**

    sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 0C49F3730359A14518585931BC711F9BA15703C6
    echo "deb [ arch=amd64,arm64 ] http://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/3.4 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-3.4.list
    sudo apt-get update
    sudo apt-get install -y mongodb-org
    
    
**Installation de mysql:**

    apt-get install mysql-server mysql-client libmysqlclient15-dev mysql-common



**Installation d’elasticsearch**

    Oracle JDK doit être installé avant de continuer.

    curl -L -O https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-5.2.2.tar.gz

    tar -xvf elasticsearch-5.2.2.tar.gz

    cd elasticsearch-5.2.2/bin


**Installation de mongo-connector**

    apt-get install python-pip
    pip install 'mongo-connector[elastic5]'

**Installation php :**

    sudo apt-get install  php5.6

    Installer php curl :

    sudo apt-get install php5.6-curl
    
    et php-mongodb et phplibssh2

    sudo pecl install mongodb

    sudo apt-get install libssh2-1-dev 
   
    pecl install ssh2

    On active les extensions en ajoutant les lignes suivantes au php.ini

	extension=mongodb.so
	extension=ssh2.so

Afin d'envoyer des mails, vous devez configurer un SMTP sur votre serveur.


**Récupérer le projet :**

    git clone https://github.com/OTELO-OSU/geo-samples.git

Rendez vous dans le dossier créé, une fois dans le dossier geo-samples, exécutez :
    
    php Init_elasticsearch_index.php 
    
Ce fichier permet de définir le template que doit utiliser elasticsearch.
    
Vous créez le fichier config manuellement.
    
Rendez vous dans Backend/config.ini :

	#ELASTICSEARCH CONFIG
	ESHOST=Host d'elasticsearch
	ESPORT=Port d'elasticsearch
	COLLECTION_NAME=Nom de la collection souhaité
	#BDD NAME
	INDEX_NAME=BDD mongoDB
	CSV_FOLDER=défini l'emplacement des Uploads des utilisateurs.
	OWNCLOUD_FOLDER=défini l'emplacement des fichiers appartennant au projet sur l'espace collaboratif
	DATAFILE_UNIXUSER=proprietaire systeme des fichiers appartennant au projet sur l'espace collaboratif
	#PROJECT
	PROJECT_NAME=Nom de projet
	SMTP=Adresse du smtp
	NO_REPLY_MAIL="Mail de no reply"
	REPOSITORY_URL=Url de l'application
	MDBHOST=Host de la base mongo
	MDBPORT=Port de la base mongo
	authSource= Le nom de votre BDD qui contiendra les jeux de données
	username = Le username de votre BDD qui contiendra les jeux de données
	password = Le mot de passe de votre BDD qui contiendra les jeux de données
	dbname=Le nom de votre BDD qui contiendra les jeux de données
	SSH_HOST=Host ssh où sont presentes les données OTELO-CLOUD (espace collaboratif de stockage)
	SSH_UNIXUSER=USER ssh
	SSH_UNIXPASSWD=password

    

**Configuration apache2**

    activer le module rewrite :
    sudo a2enmod rewrite

    Modifier la configuration apache:
    DocumentRoot /var/www/html/ORDaR/Frontend/src/


    <Directory "/var/www/html/ORDaR/Frontend/src/">
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>


**Configuration php.ini:**

	upload_max_filesize = 1G (limitation des fichiers déposés à 1GB)

	post_max_size = 1050M


	
**Demarrer la base mongo en mode replica set :**
    
    sudo mongod --replSet "rs0"

    Démarrer shell mongo et exécuter :
        rs.initiate()

    Se connecter sur la base admin:

        use admin

    Créer un utilisateur avec un rôle backup:

    db.createUser({user: "USER",pwd: "PASSWORD",roles: [ { role: "backup", db: "admin" } ]})

    Ensuite se connecter sur la base de données et créer l'utilisateur qui pourra modifier les données:

     ici:    use ORDaR

        db.createUser({user: "USER",pwd: "PASSWORD",roles: [ { role: "readWrite", db: "ORDaR" } ]})


Ensuite démarrer elasticsearch,
rendez vous dans le dossier précédemment téléchargé /bin et exécuter :
./elasticsearch


**Parametrage du fichier de configuration de mongo_connector:**

Il s'agit du user qui a les droits de backup.
Définissez un username, ainsi qu'un password.


**Initialisation du mapping d'elasticsearch:**

Afin d’initialiser le mapping, qui va définir les facettes de recherche à implémenter, il faut lancer le script Init_elasticsearch_index.php.
il doit vous retourner acknowledge:true.


**Lancez Mongo-connector**

    sudo mongo-connector -m localhost:27017 -c mongo-connector_config.json  --namespace NOMDELABDD.*

    Mongo connector permet de répliquer les données présentes dans mongoDB sur un cluster elasticsearch.

    Ci dessous un schéma explicatif de son fonctionnement :

![Alt text](/Img_doc/Mongoconnector.png?raw=true)

**Configuration de l'authentification:**


Demarrer le serveur mysql 


Executez cette commande (requiert les droits admin):
	
	mysql -h HOST-u USER -p PASSWORD < authentication.sql

Créer un utilisateur avec des droits limités à la base authentication (requiert les droits admin)

	CREATE USER 'USER'@'localhost' IDENTIFIED BY "PASSWORD";GRANT SELECT, INSERT, UPDATE, DELETE, FILE ON *.* TO 'USER'@'localhost';GRANT ALL PRIVILEGES ON `authentication`.* TO 'USER'@'localhost';

Une fois ceci fait, Editer le fichier Frontend/AuthDB.ini avec l'utilisateur précédemment créé:

	driver = mysql
	host = VOTRE_HOST
	database = authentication
	username = VOTRE_UTILISATEUR_LIMITE
	password = VOTRE_MOT_DE_PASSE_LIMITE
	charset = utf8
	collation = utf8_unicode_ci

	
La base est maintenant installée.

L'authentification s'effectue via la route /login.
On peut s'authentifier au compte utilisateur via l'authentification de l'application. Il faut préalablement avoir créé son compte via la procédure "sign up". 

L'application propose également une alternative de login via votre fournisseur d'identité et son Central Authentication Service (CAS) (si vous en possédez un et que vous faites parti de federation education recherche). 
Dans ce cas, il est nécessaire de déclarer un Service Provider :

	https://federation.renater.fr/registry

ou d'en installer un : 

	https://services.renater.fr/federation/docs/installation/sp


L'authentification vers le CAS s'effectue via la route /loginCAS.
Cette route recupère les variables contenu dans les headers HTTP fournit par le fournisseur d'identié et transmis par votre serveur shibboleth à l'application. Ces variables seront assignées aux variables de session php.

Variables utilisés:

	-HTTP_SN
	-HTTP_GIVENNAME
	-HTTP_MAIL
	
Voici un schema explicatif du fonctionnement:

![Alt text](/Img_doc/config_login.png?raw=true)


Voici un exemple de code pour configurer apache avec shibboleth:

	<Location />
	     AuthType shibboleth
	     Require shibboleth
		ShibRequestSetting applicationId ordar
	   </Location>

	<Location /loginCAS>
		# Auth Shibb
		AuthType shibboleth
		ShibRequestSetting requireSession true
		ShibRequestSetting applicationId ordar
		ShibUseHeaders On
		ShibRequireSession On
		</Location>
	
Il faut ensuite modifier la route logout afin de se deconnecter du serveur single sign in (Shibboleth, CAS).
Modifier le Redirect vers la route logout de votre service.


