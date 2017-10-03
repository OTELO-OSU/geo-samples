var APP = (function() {
    return {
        modules: {},
        init: function () {
            
        }
    }
})();

/**
 * module MAP
 * modÃ©lise la carte et les fonctionnalitÃ©s
 * associÃ©e
 *
 * @type {{init}}
 */
APP.modules.map = (function() {

    /**
     * attributs
     *  @var map : carte (objet Leaflet)
     *  @var markers : ensemble des marqueurs de la carte
     */
    var map, markers, circles;

    return {

        /**
         * methode d'initialisation
         *
         * @param htmlContainer
         *          container html de la carte
         */
        init : function(htmlContainer) {
            map = L.map(htmlContainer, {
                center: [51.505, -0.09],
                zoom : 4
            });
            var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            //map.on('click', APP.modules.affichage.closePanel);
        },

        /**
         * methode d'affichage
         * @param data
         */
        affichagePoi : function(data) {
            var markers = []
            markers._popup="";
            markers._popup._content=""
            var icon = 'station-icon.png';
            var icon2x = 'station-icon-2x.png';
            var stationIcon = L.icon({
                iconUrl: 'js/images/' + icon,
                iconRetinaUrl: 'js/images/' + icon2x,
                iconSize: [25, 41], // size of the icon
                iconAnchor: [12, 40]
            });
            data=JSON.parse(data);
            console.log(data)
            var array = $.map(data, function(value, index) {
    return [value];
});
            var lithology=[];
            array.forEach(function(k, v) {
                lithology[k.SUPPLEMENTARY_FIELDS.LITHOLOGY]=(k.SUPPLEMENTARY_FIELDS.LITHOLOGY);
                var long = k.SAMPLING_POINT[0].LONGITUDE.replace(/\s+/g, '');
                var lat = k.SAMPLING_POINT[0].LATITUDE.replace(/\s+/g, '');
                var firstProj = '+proj=lcc +lat_1=49 +lat_2=44 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';
                var secondProj = '+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs ';
                var latlng = proj4(firstProj, secondProj, [k.LONGITUDE, k.LATITUDE]);
                var marker = L.marker([long,lat]);
                marker.bindPopup(k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME);
                marker.on('click', function (e) {
                measurements='';
                    k.MEASUREMENT.forEach(function(k, v) {
                        measurement= ' <div class="item measurement_abbreviation" value="'+k[0].ABBREVIATION+'"> <div class="content"> <div class="header">'+k[0].ABBREVIATION+'</div> </div> </div>'
                        measurements+=measurement;
                    });
                    measurements='<div class="ui middle aligned selection list">'+measurements+'</div>'
                    setTimeout(function(){$('.ui.sidebar') .sidebar('setting', 'transition', 'overlay').sidebar('show');}, 50);     
                    setTimeout(function(){$('.pusher').removeClass('dimmed');}, 200);                                                      
                    $('.ui.sidebar').empty();
                    $('.ui.sidebar').append('<div class="ui styled accordion"> <div class="active title"> <i class="dropdown icon"></i> '+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+' </div> <div class="active content"> <h3>'+k.TITLE+'</h3><p> Description: '+k.SUPPLEMENTARY_FIELDS.DESCRIPTION+'<br> Sample Name: '+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'<br> Alteration degree: '+k.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE+'<br> Lithology: '+k.SUPPLEMENTARY_FIELDS.LITHOLOGY+'<br> Direction1: '+k.SUPPLEMENTARY_FIELDS.DIRECTION1+'<br> Direction2: '+k.SUPPLEMENTARY_FIELDS.DIRECTION2+'<br> Direction3: '+k.SUPPLEMENTARY_FIELDS.DIRECTION3+'<br> Latitude: '+k.SAMPLING_POINT[0].LATITUDE+' Longitude: '+k.SAMPLING_POINT[0].LONGITUDE+'</p></div> <div class="title"> <i class="dropdown icon"></i> View data </div> <div class="content">'+measurements+' </div></div>')
                    $('.ui.accordion').accordion();
                 $('.item.measurement_abbreviation').on('click', function (e) {
                    mesure=$(this).text();
                    mesure=mesure.replace("/ /g", "");
                    console.log(mesure);
                    name=k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+"_"+mesure;
                    name=name.replace("/ /g", "");
                    $("#preview").empty();
                    $("#preview").append('<iframe src="/Backend/src/index.php/preview_poi_data/'+name+'" style="width:100%; height:550px;" frameborder="0"></iframe>');
                    $(".actions a").remove();
                    $(".actions").append(' <a href="/Backend/src/index.php/download_poi_data/'+name+'"><div class="ui green  button">Download</div></a>')
                    $('.ui.modal.preview').modal('show');

                 });
                });



                marker.on('mouseover', function (e) {
                    this.openPopup();
                });
                marker.on('mouseout', function (e) {
                    this.closePopup();
                });
                markers.push(marker);
            });
            for( key in lithology){
                $('.control').append(key);
            }
            group = L.featureGroup(markers); // on met le groupe de markers dans une layer
            group.getLayers().length;
            group.addTo(map);
        },



        /**
         * methode permettant de supprimer
         * tous les markers presents sur la map
         *
         */
        clearMarkers : function() {
            group.featureGroup.clearLayers();
        },

        /**
         * methode permettant de supprimer
         * tous les cercle de selection presents sur la map
         *
         */
        clearCircles : function() {
            circles.clearLayers();
        },

        /**
         * methode permettant d'ajouter un cercle de selection
         * sur la map
         *
         * @param latlng
         *             coordonnÃ©es du cercle et du marqueur cliquÃ©
         */
        addCircle : function(latlng) {
            var circle = new L.Circle(latlng, 50, {
                color: 'black',
                opacity : 0.8,
                fillOpacity : 0.5
            });
            circles.addLayer(circle);
        },

        /**
         * methode permettant d'actualiser la carte
         * en fonction des options de filtrage
         */
        refresh : function() {
            lastTypeCombobox = $('#typeCombobox');
            var specificMeasurement = $('#measurementCombobox').val();
            if(lastTypeCombobox != null && specificMeasurement != null) {
                var type = lastTypeCombobox.val();
                APP.modules.map.clearMarkers();
                APP.modules.service.getStations(APP.modules.map.affichageStations, type, specificMeasurement);
            }
        },

        /**
         * gestion des combobox de type de prÃ©lÃ¨vement
         * enregistre la derniÃ¨re combobox modifiÃ©e
         *
         * @param lastCombobox
         *              derniÃ¨re combobox modifiÃ©e
         */
        setLastTypeCombobox : function(lastCombobox) {
            lastTypeCombobox = lastCombobox;
        }

    }
})();

APP.modules.service = (function() {
    
    return {
        getallpoi : function() {
            $.get( "/Backend/src/index.php/get_all_poi", function( data ) {
                 APP.modules.map.affichagePoi(data);
                });       
                }
}})();





window.onload = (function () {
    APP.modules.map.init('map');
    APP.init();
    APP.modules.service.getallpoi();
})();