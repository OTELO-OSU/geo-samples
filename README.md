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
    
** Utilisation **

Lancez Mongo-connector

sudo mongo-connector -m localhost:27017 -c mongo-connector_config.json  --namespace NOMDELABDD.*

Mongo connector permet de répliquer les données présentes dans mongoDB sur un cluster elasticsearch.

** Modification affichage**

Si vous souhaitez modifier l'affichage des metadonnées dans l'application, il faut se rendre dans le fichier Frontend/src/js/index.js , ligne 159
$('.ui.sidebar.right').append(HTMLCONTENT)
Il faut modifier le HTML present dans append.
