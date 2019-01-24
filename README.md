# Geosamples
Geographical representation

**Fonctionnement:**

Le frontend effectue un appel AJAX qui va se connecter au backend de l'application qui se connecte à l'api elasticsearch.
Il est possible de trier les resultats obtenu avec des filtres ( lithology , date, mesure).

Ces filtres sont aussi applicable sur une zone de la map defini, pour cela il faut maintenir "CTRL" et choisir un lieux, lorsque que vous relacherez ,
la map s'actualisera.

En selectionnant un point, vous avez accées aux metadonnées, aux données,au données brute, ainsi que des images.
Il est possible de les consulter en ligne ou de les telecharger.

**Configuration:**

Le fichier de configuration se trouve dans Backend/config.ini

    #ELASTICSEARCH CONFIG
    ESHOST=localhost
    ESPORT=9200
    #BDD NAME
    INDEX_NAME=ordar
    CSV_FOLDER="/data/applis/ORDaR/Uploads/"


**Installation d’elasticsearch**

    Oracle JDK doit être installé avant de continuer.

    curl -L -O https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-5.2.2.tar.gz

    tar -xvf elasticsearch-5.2.2.tar.gz

    cd elasticsearch-5.2.2/bin


**Installation de mongo-connector**

    apt-get install python-pip
    pip install 'mongo-connector[elastic5]'
    
**Installation de mysql:**

    apt-get install mysql-server mysql-client libmysqlclient15-dev mysql-common
    
    
 **Configuration de l'authentification:**


Demarrer le serveur mysql 


Executez cette commande (requiert les droits admin):
	
	mysql -h HOST-u USER -p PASSWORD < authentication_geosamples.sql

Créer un utilisateur avec des droits limités à la base authentication (requiert les droits admin)

	CREATE USER 'USER'@'localhost' IDENTIFIED BY "PASSWORD";GRANT SELECT, INSERT, UPDATE, DELETE, FILE ON *.* TO 'USER'@'localhost';GRANT ALL PRIVILEGES ON `authentication_geosamples`.* TO 'USER'@'localhost';

Une fois ceci fait, Editer le fichier Backend/AuthDB.ini avec l'utilisateur précédemment créé:

	driver = mysql
	host = VOTRE_HOST
	database = authentication_geosamples
	username = VOTRE_UTILISATEUR_LIMITE
	password = VOTRE_MOT_DE_PASSE_LIMITE
	charset = utf8
	collation = utf8_unicode_ci

	
La base est maintenant installée.

L'authentification s'effectue via la route /login.
On peut s'authentifier au compte utilisateur via l'authentification de l'application. Il faut préalablement avoir créé son compte via la procédure "sign up". 

L'application propose également une alternative de login via votre fournisseur d'identité et son Central Authentication Service (CAS) (si vous en possédez un et que vous faites parti de federation education recherche). 
Dans ce cas, il est nécessaire de déclarer un Service Provider :
ou d'en installer un : 

	https://services.renater.fr/federation/docs/installation/sp


L'authentification vers le CAS s'effectue via la route /loginCAS.
Cette route recupère les variables contenu dans les headers HTTP fournit par le fournisseur d'identié et transmis par votre serveur shibboleth à l'application. Ces variables seront assignées aux variables de session php.

Variables utilisés:

	-HTTP_SN
	-HTTP_GIVENNAME
	-HTTP_MAIL
    
    
**Utilisation**

Lancez Mongo-connector

sudo mongo-connector -m localhost:27017 -c mongo-connector_config.json  --namespace NOMDELABDD.*

Mongo connector permet de répliquer les données présentes dans mongoDB sur un cluster elasticsearch.

**Modification affichage**

Si vous souhaitez modifier l'affichage des metadonnées dans l'application, il faut se rendre dans le fichier Frontend/src/js/index.js , ligne 159
$('.ui.sidebar.right').append(HTMLCONTENT)
Il faut modifier le HTML present dans append.

**Authentification**

Le compte admin par défaut est admin@geosample.fr, son mot de passe est : G30Sample@
Il faut créé un nouveau compte utilisateur et le definir en tant que admin et supprimé le compte par défaut.

