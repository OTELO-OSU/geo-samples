# Organisation  


## Organisation des bases de données mongo et mysql :

**Un deploiement comporte 2 bases de données mongo:**

    - nom_du_projet (le nom de votre Bdd: base de données principale)
    - nom_du_projet_sanbox ( bdd bac a sable, permettant d'inserer les données en attente de validation par le referent)
    
 Dans le cas d'une implémentation des scripts de moissonage d'un espace projet collaboratif,les données sont indexé directement dans la collection principale.
 
 **Détails des clés mongo:**
 
    _id: 
    INTRO:
            TITLE: Titre
            LANGUAGE: Langage
            FILE_CREATOR:   
                        FIRST_NAME: Prénom
                        NAME: Nom
                        DISPLAY_NAME: Prénom et nom
                        MAIL: Mail du créateur
            DATA_DESCRIPTION: Description des données
            PUBLISHER: Editeur
            SCIENTIFIC_FIELD: Champs scientifiques
            INSTITUTION: Institutions
            METHODOLOGY:
                        NAME:Nom
                        DESCRIPTION:Description
            MEASUREMENT:
                        NATURE
                        ABBREVIATION
                        UNIT
           LICENSE:Licence
           ACCESS_RIGHT:Droits d'accés
           METADATA_DATE:Date de dernieres modifications des metadonnées
           CREATION_DATE:Date de création initiale du jeu de données 
           UPLOAD_DATE:Date d'ajout dans l'entrepot
           PUBLICATION_DATE:Date de publication des données
	   SUPPLEMENTARY_FIELDS:Ajout de metadonné spécifiques par l'utilisateur
	   SAMPLING_POINT:Informations sur le sampling_point
	   SAMPLING_DATE
	   
    DATA:
          FILES:
                DATA_URL:Denomination du fichier
                FILETYPE:Extension du fichier
	  SAMPLES:DATA permettant une indexation

**Le systeme comporte une base de données mysql pour la gestion des utilisateurs :**

***Schéma base de données authentification***:

![Alt text](/Img_doc/schema_auth.png?raw=true)

***Schéma de vie des données ***:

![Alt text](/Img_doc/schema_de_vie.png?raw=true)





