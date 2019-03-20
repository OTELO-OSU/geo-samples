var APP = (function() {
    return {
        modules: {},
        group: null,
        data_raw:null,
        init: function() {}
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
     var map, markers, areaSelect;
     return {
        /**
         * methode d'initialisation
         *
         * @param htmlContainer
         *          container html de la carte
         */
         init: function(htmlContainer) {
            map = L.map(htmlContainer, {
                center: [51.505, -0.09],
                zoom: 4
            });
            var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            var ign = L.tileLayer(
                'https://wxs.ign.fr/9bci2kf4ow18mxkruzqcl3pi/geoportail/wmts?service=WMTS&request=GetTile&version=1.0.0&tilematrixset=PM&tilematrix={z}&tilecol={x}&tilerow={y}&layer=ORTHOIMAGERY.ORTHOPHOTOS&format=image/jpeg&style=normal',
                {
                    minZoom : 0,
                    maxZoom : 18,
                    tileSize : 256,
                    attribution : "IGN-F/Géoportail"
                }).addTo(map);
            var baseLayers = {"IGN" : ign, "OpenStreetMap" : osm};
            L.control.scale({'position':'bottomleft','metric':true,'imperial':false}).addTo(map);
            L.control.layers(baseLayers, {}).addTo(map);
            $(document).keydown(function(event) {
                if (event.which == "17") {
                    areaSelect = L.areaSelect({
                        width: 300,
                        height: 300
                    });
                    areaSelect.addTo(map);
                }
            });
            $(document).keyup(function(event) {
                if (event.which == "17") {
                    bounds = areaSelect.getBounds();
                    APP.modules.service.searchlithologyanddateandmesure($("input[name='lithology']")[0].value, $("input[name='measurement_abbreviation']")[0].value, $('input[name=mindate]')[0].value, $('input[name=maxdate]')[0].value, bounds['_southWest']['lat'], bounds['_northEast']['lat'], bounds['_northEast']['lng'], bounds['_southWest']['lng']);
                    areaSelect.remove();
                    delete areaSelect;
                }
            });
        },
        affichageinfo:function(k){
            //$('.ui.modal.preview').modal('hide');

           // $('.ui.dimmer.modals').css( "background-color", "rgba(0,0,0,0)" );
                    $('.ui.sidebar.right').css("z-index","2000");

            k=k.toUpperCase();
            console.log(APP.data_raw);
            console.log(k)
            k=APP.data_raw[k]


            measurements = '';
            rawdatas = '';
            supplementary_fields='';
                        if (k.MEASUREMENT) {
                            k.MEASUREMENT.forEach(function(k, v) {
                                if ((new RegExp('_RAW')).test(k[0].ABBREVIATION)) {
                                    rawdata = ' <div class="item measurement_abbreviation_raw" ><input type="hidden" value="' + k[0].ABBREVIATION + '"> <div class="content"> <div class="header">' + k[0].ABBREVIATION + '</div><div>' + k[0].NATURE + '</div>  <a href="/download_poi_raw_data/' + k[0].ABBREVIATION + '"><div class="ui green  button">Download</div></a></div> </div>'
                                    rawdatas += rawdata;
                                } else {
                                    measurement = ' <div class="item measurement_abbreviation" ><input type="hidden" value="' + k[0].ABBREVIATION + '"> <div class="content"> <div class="header">' + k[0].ABBREVIATION + '</div><div>' + k[0].NATURE + '</div> </div> </div>'
                                    measurements += measurement;
                                }
                            });
                            if (measurements != '') {
                                measurements = '<div class="ui middle aligned selection list">' + measurements + '</div>'
                            }
                            if (rawdatas != '') {
                                rawdatas = '<div class="ui middle aligned selection list">' + rawdatas + '</div>'
                            }
                        }
                        if (measurements != '') {
                            measurements = '<div class="title"> <i class="dropdown icon"></i> Data </div> <div class="content">' + measurements + ' </div>'
                        }
                        if (rawdatas != '') {
                            rawdatas = '<div class="title"> <i class="dropdown icon"></i> Raw data </div> <div class="content">' + rawdatas + ' </div>';
                        }

                        if (k.SUPPLEMENTARY_FIELDS) {
                            supplementary_fields_array=[];
                             $.map(k.SUPPLEMENTARY_FIELDS, function(k3, v3) {  
                            supplementary_field='';
                            if (v3=='REFERENT') {
                               k3.forEach(function(ref, val) {
                                $.map(ref, function(k4, v4) {  
                                     supplementary_field += v4+': '+k4+'<br>'
                                });
                                });
                            }else if (v3=='ANALYST') {
                               k3.forEach(function(ref, val) {
                                $.map(ref, function(k4, v4) {  
                                     supplementary_field += v4+': '+k4+'<br>'
                                });
                                });
                            }
                            else if (v3=='CORE_DETAILS') {
                               k3.forEach(function(ref, val) {
                                $.map(ref, function(k4, v4) {  
                                     supplementary_field += v4+': '+k4+'<br>'
                                });
                                });
                            }
                            else{

                                supplementary_field = v3+': '+k3
                            }

                            supplementary_fields_array.push(supplementary_field);
                            });
                            supplementary_fields_array=supplementary_fields_array.sort()

                             supplementary_fields_array.forEach(function(ref, val) {
                                supplementary_fields+='<br>'+ref;
                            });
                            if (supplementary_fields != '') {
                                supplementary_fields = '<div class="ui middle aligned selection list">' + supplementary_fields + '</div>'
                            }
                           
                        }
                        if (supplementary_fields != '') {
                            supplementary_fields = '<div class="title"> <i class="dropdown icon"></i> Supplementary fields </div> <div class="content">' + supplementary_fields + ' </div>'
                        }



                        pictures = '';
                        picturemetas = '';
                        if (k.PICTURES) {
                            for (key in k.PICTURES) {
                                picture = k.PICTURES[key].DATA_URL;
                                name = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME;
                                if ((new RegExp('_OUTCROP')).test(picture) || (new RegExp('_SAMPLE')).test(picture)) {
                                    picturemeta = '<div class="item pictures" ><input type="hidden" value="' + k.PICTURES[key].DATA_URL + '"> <div class="content picture" > <img class="ui fluid image" src="/preview_img/' + name + '/' + picture + '""</img><div class="header">' + k.PICTURES[key].DATA_URL + '</div></div></div>';
                                    picturemetas += picturemeta;
                                } else {
                                    picture = ' <div class="item pictures" ><input type="hidden" value="' + k.PICTURES[key].DATA_URL + '"> <div class="content"> <div class="header">' + k.PICTURES[key].DATA_URL + '</div></div> </div>'
                                    pictures += picture;
                                }
                            }
                            picturemetas = '<div class="ui middle aligned selection list">' + picturemetas + '</div>';
                            pictures = '<div class="ui middle aligned selection list">' + pictures + '</div>';
                            pictures = '<div class="title"> <i class="dropdown icon"></i> Pictures </div> <div class="content">' + pictures + ' </div>';
                        }
                        setTimeout(function() {
                            $('.ui.sidebar.right').sidebar('setting', 'transition', 'overlay').sidebar('show');
                        }, 50);
                        setTimeout(function() {
                            $('.pusher').removeClass('dimmed');
                        }, 200);
                        $('.ui.sidebar.right').empty();
                        referent = '';
                        if(k.SUPPLEMENTARY_FIELDS.REFERENT){
                            k.SUPPLEMENTARY_FIELDS.REFERENT.forEach(function(ref, val) {
                                if (ref.NAME_REFERENT) {
                                    referent += '<br> Referent Name: ' + ref.NAME_REFERENT + '<br> Referent First name: ' + ref.FIRST_NAME_REFERENT + '<br> Referent mail: ' + ref.MAIL_REFERENT;
                                }
                            });
                        }
                        institution="";
                         if(k.INSTITUTION){
                            k.INSTITUTION.forEach(function(inst, val) {
                                if (inst.NAME) {
                                    institution += '<br> Institution: ' + inst.NAME;
                                }
                            });
                        }
                        if (k.SUPPLEMENTARY_FIELDS.LITHOLOGY ) {
                        lithology='<br> Lithology: ' + k.SUPPLEMENTARY_FIELDS.LITHOLOGY ;
                        }
                         else if (k.SUPPLEMENTARY_FIELDS.LITHOLOGY1 ) {
                        lithology='<br> Lithology: ' + k.SUPPLEMENTARY_FIELDS.LITHOLOGY1 ;
                        }
                        else{
                            lithology='<br> Lithology: Inconnu' ;
                        }
                         if (k.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE ) {
                         alteration_degrees='<br>Alteration degree: ' + k.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE;
                        }
                        else{
                            alteration_degrees="";
                        }
                        if (k.SAMPLING_POINT[0].ELEVATION!='') {
                         elevation='Elevation: ' + k.SAMPLING_POINT[0].ELEVATION;
                        }
                        else{
                            elevation="";
                        }
                        $('.ui.sidebar.right').append('<div class="ui styled accordion"> <div class="active title"> <i class="dropdown icon"></i> ' + k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME + ' </div> <div class="active content"> <h3>' + k.TITLE.substr(0, k.TITLE.lastIndexOf("_")) + '</h3><p> Description: ' + k.SUPPLEMENTARY_FIELDS.DESCRIPTION + '<br> Sample Name: ' + k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME +alteration_degrees + referent + lithology+institution+'<br> Latitude: ' + k.SAMPLING_POINT[0].LATITUDE + ' Longitude: ' + k.SAMPLING_POINT[0].LONGITUDE + '<br>'+elevation+'</p>' + picturemetas + '</div>' + measurements + pictures + rawdatas+supplementary_fields)
                        $('.ui.accordion').accordion();
                        $('.item.measurement_abbreviation').on('click', function(e) {
                            mesure = $(this).children()[0].value;
                            mesure = mesure.replace("/ /g", "");
                            name = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME + "_" + mesure;
                            name = name.replace("/ /g", "");
                            $("#preview").empty();
                            $("#preview").append('<iframe src="/preview_poi_data/' + name + '" style="width:100%; height:550px;" frameborder="0"></iframe>');
                            $(".actions a").remove();
                            $(".actions .download").remove();
                            $(".actions").append(' <a href="/download_poi_data/' + name + '"><div class="ui green  button">Download</div></a>')
                            $('.ui.modal.preview').modal('show');
                        });
                        $('.item.pictures').on('click', function(e) {
                            picture = $(this).children()[0].value;
                            name = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME;
                            name = name.replace("/ /g", "");
                            $("#preview").empty();
                            $("#preview").append('<img class="ui fluid image" src="/preview_img/' + name + '/' + picture + '""</img>');
                            $(".actions a").remove();
                            $(".actions .download").remove();
                            $(".actions").append(' <a href="/download_img/' + name + '/' + picture + '"><div class="ui green  button">Download</div></a>')
                            $('.ui.modal.preview').modal('show');
                        });
        },
        /**
         * methode d'affichage
         * @param data
         */
         affichagePoi: function(data, all, updatedate, updatemesure, updatelithology) {
            data = JSON.parse(data);
            $('.message').empty();
            if (APP.group != null) {
                APP.group.clearLayers();
                APP.group=null;
            }
            if (data == null || data.length == 0) {
                $('.message').append('<div class="ui container"><div class="column"><div class="ui negative message">  <div class="header"> No data found </div> <p>Please try again later or with others filters</p></div></div></div>');
            } else {
                var same_location=[]; 
                var array2=[];
                var markers = []
                var markers = L.markerClusterGroup({ chunkedLoading: true });
                markers._popup = "";
                markers._popup._content = ""
                var icon = 'station-icon.png';
                var icon2x = 'station-icon-2x.png';
                var marker=null;
                var stationIcon = L.icon({
                    iconUrl: 'js/images/' + icon,
                    iconRetinaUrl: 'js/images/' + icon2x,
                    iconSize: [25, 41], // size of the icon
                    iconAnchor: [12, 40]
                });
               
                var lithology = [];
                var creationdate = [];
                lithology['all'] = 'all';
                var measurement_abbreviation = [];
                var measurement_nature = [];
                
                $.map(data, function(k2, v2) {
                    console.log(k2)
                
                    marker=null;
                    if (k2.COORDINATES) {

                    var long = k2.COORDINATES.LONG.replace(/\s+/g, '');
                    console.log(long)
                    var lat = k2.COORDINATES.LAT.replace(/\s+/g, '');
                    marker = L.marker([lat, long]);
                    var firstProj = '+proj=lcc +lat_1=49 +lat_2=44 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';
                    var secondProj = '+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs ';
                    console.log(lat)
                    console.log(long)

                     var latlng = proj4(firstProj, secondProj, [lat,long]);
                       marker.on('click', function(e) {
                        var orientation=null;
                        $("#preview").empty();
                            APP.data_raw=k2;
                            var samples= new Array();
                            var show_popup=null;
                            var picture_localisation = null;
                            var name_localisation = null;
                            $.map(k2, function(k, v) {  
                        if (k.PICTURES) {
                            for (key in k.PICTURES) {
                                picture = k.PICTURES[key].DATA_URL;
                                name = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME;
                                if ((new RegExp('_LOCALISATION')).test(picture)) {
                                picture_localisation = k.PICTURES[key].DATA_URL;
                                name_localisation = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME;


                                }
                            }
                        }

                            if (k.SUPPLEMENTARY_FIELDS) {

                                if (k.SUPPLEMENTARY_FIELDS.CORE_DETAILS || k.SUPPLEMENTARY_FIELDS.BLOCK ) {
                                    if (k.SUPPLEMENTARY_FIELDS.CORE_DETAILS[0].CORE.toUpperCase() == 'YES' && k.SAMPLING_POINT[0].ELEVATION != null) {
                                        $('.preview .header').empty();
                                        $('.preview .header').append('Core');
                                        $('#preview').append('<div id="line"></div><style>.event{top:-30px!important;}</style>');
                                        object= new Object();
                                        object.date=k.SAMPLING_POINT[0].ELEVATION;
                                        object.content='<a onclick=\"APP.modules.map.affichageinfo(\''+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'\');\">'+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME +'</a>';
                                        samples.push(object);
                                          samples.sort(function(a, b) {
                                            return parseFloat(b.date) - parseFloat(a.date);
                                        });
                                        orientation='vertical';
                                        
                                    }
                                    else if (k.SUPPLEMENTARY_FIELDS.BLOCK.toUpperCase() == 'YES' && k.SAMPLING_POINT[0].SAMPLING != null){
                                        $('.preview .header').empty();
                                        $('.preview .header').append('OUTCROP');
                                        $('#preview').append('<div id="line"></div>');
                                        object= new Object();
                                        object.date=k.SAMPLING_POINT[0].SAMPLING;
                                        object.content='<a onclick=\"APP.modules.map.affichageinfo(\''+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'\');\">'+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'</a>';
                                        samples.push(object);
                                            samples.sort(function(a, b) {
                                            return parseFloat(a.date) - parseFloat(b.date);
                                        });
                                        orientation='horizontal';
                                    }
                                }
                                if (k.SUPPLEMENTARY_FIELDS.STATION) {
                                     if (k.SUPPLEMENTARY_FIELDS.STATION.toUpperCase() == 'YES' && k.SAMPLING_DATE[0] != null){
                                        $('.preview .header').empty();
                                        $('.preview .header').append('Samples by sampling date');
                                        $('#preview').append('<div id="line"></div>');
                                        object= new Object();
                                        object.date=k.SAMPLING_DATE[0];
                                        object.content='<a onclick=\"APP.modules.map.affichageinfo(\''+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'\');\">'+k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME+'</a>';
                                        samples.push(object);
                                          samples.sort(function(a, b) {
                                            return parseFloat(a.date) - parseFloat(b.date);
                                        });
                                        orientation='horizontal';
                                    }

                                }
                           
                            $(".actions a").remove();
                            $(".actions .download").remove();
                            if (Object.keys(k2).length>2) {
                                show_popup=true;
                        }else{
                                 if (k.SUPPLEMENTARY_FIELDS) {

                            APP.modules.map.affichageinfo(k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME);
                                show_popup=false;
                                }
                            }
                            }
                            });
                           
               
                              
                            if (orientation=='vertical') {
                                 prevArrow= '<i class="angle up icon"></i>',
                                 nextArrow= '<i class="angle down icon"></i>'
                            }else{
                                 prevArrow= '<i class="angle left icon"></i>',
                                 nextArrow= '<i class="angle right icon"></i>'
                            }

                             $('#line').roadmap(samples, {
                                orientation:orientation,
                                eventsPerSlide: 5,
                                slide:1,
                                prevArrow: prevArrow,
                                nextArrow: nextArrow

                            });
                             

                             if (show_popup===true) {
                                 if (picture_localisation !== null) {
                                    $('#preview.scrolling.content').append('<img class="ui centered big  image" src="/preview_img/' + name_localisation + '/' + picture_localisation + '""</img>');
                                }
                                $('.ui.longer.modal.preview').modal({
    onVisible: function () {
        $(".ui.longer.modal.preview").modal("refresh");
    },
      onHide: function(){
    
       $('.ui.sidebar.right').css("z-index","0");
            //$('.ui.dimmer.modals').css( "background-color", "rgba(0,0,0,0.85)" );

        },
}).modal('show');

                             }

                    });
                       marker.on('mouseover', function(e) {
                        this.openPopup();
                    });
                    marker.on('mouseout', function(e) {
                        this.closePopup();
                    });
                        markers.addLayer(marker);
                        console.log(marker)
                    }
                    $.map(k2, function(k, v) {                    
                    
                    console.log(k)
                    console.log(marker);
       
                       // console.log(k)

                        if (k.SUPPLEMENTARY_FIELDS) {
                     if (Object.keys(k2).length<2) {
                     marker.bindPopup(k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME);
                 }
             
                     if (k.SUPPLEMENTARY_FIELDS.LITHOLOGY!=null)  {
                    lithology[k.SUPPLEMENTARY_FIELDS.LITHOLOGY] = (k.SUPPLEMENTARY_FIELDS.LITHOLOGY);
                    }

                    creationdate.push(k.SAMPLING_DATE[0]);

                        if (k.MEASUREMENT) {
                    k.MEASUREMENT.forEach(function(k, v) {
                        mesure = k[0].ABBREVIATION.split("_");
                        if (mesure && new RegExp('_RAW').test(k[0].ABBREVIATION) == false) {
                            measurement_abbreviation[k[0].ABBREVIATION] = (k[0].ABBREVIATION);
                            measurement_nature[k[0].ABBREVIATION] = (k[0].NATURE);
                        }

                    });
                }
                    //var long = k.SAMPLING_POINT[0].LONGITUDE.replace(/\s+/g, '');
                    //var lat = k.SAMPLING_POINT[0].LATITUDE.replace(/\s+/g, '');

                
                    //var latlng = proj4(firstProj, secondProj, [k.LONGITUDE, k.LATITUDE]);
                    //var marker = L.marker([lat, long]);

                  

                    
                      
                    
                    }

                });
                });
                append = "";
                var minDate = creationdate.reduce(function(a, b) {
                    return a < b ? a : b;
                });
                var maxDate = creationdate.reduce(function(a, b) {
                    return a > b ? a : b;
                });
                for (key in measurement_abbreviation) {
                    item = '<div class="item" title="' + measurement_nature[key] + '">' + key + '</div>';
                    measurement_abbreviation += item;
                }
                if (all == true) {
                    $('.control').empty();
                    $('.control button').remove();
                    $('.control').append('<h1>Sort results</h1>')
                    for (key in lithology) {
                        item = '<div class="item">' + key + '</div>';
                        lithology += item;
                    }
                    lithology = '<div class="ui one column"><div class="ui selection dropdown lithology"><input type="hidden" name="lithology"> <i class="dropdown icon"></i><div class="default text">All</div><div class="menu">' + lithology + ' </div></div></div>';
                    append += lithology;
                }
                if (updatelithology == true) {
                    $('.control .lithology').remove();
                    for (key in lithology) {
                        item = '<div class="item">' + key + '</div>';
                        lithology += item;
                    }
                    lithology = '<div class="ui one column"><div class="ui selection dropdown lithology"><input type="hidden" name="lithology"> <i class="dropdown icon"></i><div class="default text">All</div><div class="menu">' + lithology + ' </div></div></div>';
                    append += lithology;
                }
                if (updatedate == true) {
                    $('.control button').remove();
                    $('.control .dates').remove();
                    date = '<div class="dates"><div class="ui input"><input  type="text" name="mindate" value="' + minDate + '"></div><div class="ui input"><input class="ui input" type="text"   name="maxdate" value="' + maxDate + '"></div></div>';
                    append += date;
                }
                if (updatemesure == true) {
                    $('.control .button').remove();
                    $('.control .measurement_abbreviation').remove();
                    measurement_abbreviation = '<div class="ui one column"><div class="ui selection dropdown measurement_abbreviation"><input type="hidden" name="measurement_abbreviation"> <i class="dropdown icon"></i><div class="default text">Select a mesure</div><div class="menu">' + measurement_abbreviation + ' </div></div></div>';
                    append += measurement_abbreviation;
                }
                $('.control').append(append);
                $(".control .dates input").datepicker({
                    minDate: new Date(minDate),
                    maxDate: new Date(maxDate),
                    dateFormat: 'yy-mm-dd'
                })
                $('input[name=measurement_abbreviation]').unbind('change');
                $("input[name='measurement_abbreviation']").on('change', function(e) {
                    APP.modules.service.searchlithologyanddateandmesure($("input[name='lithology']")[0].value, $("input[name='measurement_abbreviation']")[0].value, $('input[name=mindate]')[0].value, $('input[name=maxdate]')[0].value);
                })
                $('.filter').on('click', function(e) {
                    $('.sidebar.left').sidebar('setting', 'transition', 'overlay').sidebar('toggle');
                })
                $('.ui.dropdown.lithology').dropdown({
                    onChange: function(value, text, $selectedItem) {
                        if (value == 'all') {
                            APP.modules.service.getallpoi();
                        } else {
                            APP.modules.service.searchlithology(value);
                        }
                    }
                });
                $('.ui.dropdown.measurement_abbreviation').dropdown();
                $('.ui.dropdown.lithology').dropdown({
                    onChange: function(value, text, $selectedItem) {
                        if (value == 'all') {
                            APP.modules.service.getallpoi();
                        } else {
                            APP.modules.service.searchlithology(value);
                        }
                    }
                });
                $('.ui.dropdown.measurement_abbreviation').dropdown();
                $('input[name=mindate]').unbind('change');
                $('input[name=maxdate]').unbind('change');
                $('input[name=mindate]').on('change', function(e) {
                    APP.modules.service.searchlithologyanddate($('input[name=lithology]')[0].value, $('input[name=mindate]')[0].value, $('input[name=maxdate]')[0].value);
                })
                $('input[name=maxdate]').on('change', function(e) {
                    APP.modules.service.searchlithologyanddate($('input[name=lithology]')[0].value, $('input[name=mindate]')[0].value, $('input[name=maxdate]')[0].value);
                })
                APP.group = markers; // on met le groupe de markers dans une layer
                map.addLayer(markers);
                APP.group.getLayers().length;
                APP.group.addTo(map);
                bounds = markers.getBounds();
                map.fitBounds(bounds);
          }
        },
    }
})();
APP.modules.service = (function() {
    return {
        getallpoi: function() {
            $.get("/get_all_poi", function(data) {
                APP.modules.map.affichagePoi(data, true, true, true);
            });
        },
        getpoisorted: function(json, updatedate, updatemesure, updatelithology) {
            $.post("/get_poi_sort", {
                json: json
            }, function(data) {
                APP.modules.map.affichagePoi(data, false, updatedate, updatemesure, updatelithology);
            });
        },
        getdata: function(json) {
            $.post("/get_poi_type_data", {
                json: json
            }, function(data) {
                $("#preview").empty();
                $("#preview").append(data);
                $(".actions a").remove();
                $(".actions .download").remove();
                $(".actions").append('<div class="ui green button download">Download</div>')
                $(".actions .download").on('click', function(e) {
                    APP.modules.service.downloaddata(json);
                });
            });
        },
        downloaddata: function(json) {
            $.post("/download_poi_type_data", {
                json: json
            }, function(data) {
                var pom = document.createElement('a');
                pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data));
                json = JSON.parse(json);
                pom.setAttribute('download', json.mesure + '_' + json.lithology + '_' + json.mindate + '_' + json.maxdate + '.csv');
                if (document.createEvent) {
                    var event = document.createEvent('MouseEvents');
                    event.initEvent('click', true, true);
                    pom.dispatchEvent(event);
                } else {
                    pom.click();
                }
            });
        },
        searchlithologyanddateandmesure: function(lithology, mesure, mindate, maxdate, lat1, lat2, lon1, lon2) {
            obj = {
                "lithology": lithology,
                'mesure': mesure,
                "mindate": mindate,
                "maxdate": maxdate,
                "lat": {
                    "lat1": lat1,
                    "lat2": lat2
                },
                "lon": {
                    "lon1": lon1,
                    "lon2": lon2
                },
            };
            json = JSON.stringify(obj);
            if (lat1 && lat2 && lon1 && lon2) {
                APP.modules.service.getpoisorted(json, true, true, true);
            } else {
                APP.modules.service.getpoisorted(json, false, false, false);
            }
            APP.modules.service.getdata(json);
            $('.control .button').remove();
            $('.control').append('<div class="ui button">Preview CSV for ' + mesure.toUpperCase() + '</div>')
            $('.control .button').on('click', function(e) {
                $('.ui.modal.preview').modal('show');
            });
        },
        searchlithology: function(lithology) {
            obj = {
                "lithology": lithology
            };
            json = JSON.stringify(obj);
            APP.modules.service.getpoisorted(json, true, true);
        },
        searchlithologyanddate: function(lithology, mindate, maxdate) {
            obj = {
                "lithology": lithology,
                "mindate": mindate,
                "maxdate": maxdate
            };
            json = JSON.stringify(obj);
            APP.modules.service.getpoisorted(json, false, true);
        },
    }
})();
APP.modules.account = (function() {
  return {
      check_signup: function() {
          $('.signup').form({
              fields: {
                  usermail: {
                      identifier: 'email',
                      rules: [{
                          type: 'email',
                          prompt: 'Please enter a valid email'
                      }]
                  },
                  username: {
                      identifier: 'name',
                      rules: [{
                          type: 'regExp[^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ -.,]*$]',
                          prompt: 'Please enter a valid name'
                      }]
                  },
                  userfirstname: {
                      identifier: 'firstname',
                      rules: [{
                          type: 'regExp[^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ -.,]*$]',
                          prompt: 'Please enter a valid firstname'
                      }]
                  },
                  password: {
                      identifier: 'password',
                      rules: [{
                          type: 'regExp[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[$@$!%*?&])[A-Za-z\\d$@$!%*?&]{8,}$/]',
                          prompt: 'Password can not be less than 8 characters and must have at least one number, one uppercase and one lowercase, and one special characters'
                      }]
                  },
                  password_confirm: {
                      identifier: 'password_confirm',
                      rules: [{
                          type: 'match[password]',
                          prompt: 'Password verification failed'
                      }]
                  }
              }
          });

          if (sessionStorage.getItem('mail')) {
            $("input[name='email']").val(sessionStorage.getItem('mail'));
            sessionStorage.removeItem('mail')
        }
        if (sessionStorage.getItem('name')) {
            $("input[name='name']").val(sessionStorage.getItem('name'));
            sessionStorage.removeItem('name')
        }
        if (sessionStorage.getItem('firstname')) {
            $("input[name='firstname']").val(sessionStorage.getItem('firstname'));
            sessionStorage.removeItem('firstname')
        }

        

    },
    check_login: function() {
      $('.login').form({
          fields: {
              usermail: {
                  identifier: 'email',
                  rules: [{
                      type: 'email',
                      prompt: 'Please enter a valid email'
                  }]
              },
              password: {
                  identifier: 'password',
                  rules: [{
                      type: 'regExp[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[$@$!%*?&])[A-Za-z\\d$@$!%*?&]{8,}$/]',
                      prompt: 'Password can not be less than 8 characters and must have at least one number, one uppercase and one lowercase, and one special characters'
                  }]
              }
          }
      });
  },
  check_recover: function() {
      $('.recover').form({
          fields: {
              usermail: {
                  identifier: 'email',
                  rules: [{
                      type: 'email',
                      prompt: 'Please enter a valid email'
                  }]
              }
          }
      });
  },
  check_change_password: function() {
      $('.change_password').form({
          fields: {
              password: {
                  identifier: 'password',
                  rules: [{
                      type: 'regExp[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[$@$!%*?&])[A-Za-z\\d$@$!%*?&]{8,}$/]',
                      prompt: 'Password can not be less than 8 characters and must have at least one number, one uppercase and one lowercase, and one special characters'
                  }]
              },
              password_confirm: {
                  identifier: 'password_confirm',
                  rules: [{
                      type: 'match[password]',
                      prompt: 'Password verification failed'
                  }]
              }
          }
      });
  },
  check_myaccount: function() {
      $('.myaccount').form({
          fields: {
              username: {
                  identifier: 'name',
                  rules: [{
                      type: 'regExp[^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ -.,]*$]',
                      prompt: 'Please enter a valid name'
                  }]
              },
              userfirstname: {
                  identifier: 'firstname',
                  rules: [{
                      type: 'regExp[^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ -.,]*$]',
                      prompt: 'Please enter a valid firstname'
                  }]
              },
              
          }
      });
      
  },
  check_clicked: function(e, name_CSRF, value_CSRF, name, firstname, mail, type,project_name,project_name_modify) {
      var action = e.target.name;
      if (action == 'approve') {
          var action = "approveuser";
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Are you sure to approve ' + mail + '?  ');
          $(".modal.user .content").append('<form class="ui form ' + action + ' " action="' + action + '" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"> <div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit green button" >Yes</button> </div> </form>');
          $('.ui.modal.user').modal('show');
      } else if (action == 'disable') {
          var action = "disableuser";
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Are you sure to disable ' + mail + '?');
          $(".modal.user .content").append('<form class="ui form ' + action + ' " action="' + action + '" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"> <div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit button" >Yes</button> </div> </form>');
          $('.ui.modal.user').modal('show');
      } else if (action == 'remove') {
          var action = "removeuser";
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Are you sure to remove ' + mail + '?');
          $(".modal.user .content").append('<form class="ui form ' + action + ' " action="' + action + '" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"> <div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit red button" >Yes</button> </div> </form>');
          $('.ui.modal.user').modal('show');
      } else if (action == 'modify') {
        /*$.post("get_user_projects",{ mail_user: mail} ,function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });*/
       
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Modify informations: ' + mail);
          $(".modal.user .content").append('<form class="ui large form myaccount" method="post" action="/modifyuser"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"> <div class="ui error message"></div> <div class="ui stacked segment"> <div class="field"> <div class="ui left icon input"> <i class="user icon"></i> <input type="text" name="name" value="' + name + '" placeholder="Name"> </div> </div> <div class="field"> <div class="ui left icon input"> <i class="user icon"></i> <input type="text" name="firstname" value="' + firstname + '" placeholder="Firstname"> </div></div> <div class="field"><div class="ui selection dropdown"><input type="hidden" name="type"><i class="dropdown icon"></i><div class="default text">Access right</div><div class="menu"><div class="item" data-value="0">User</div> <div class="item" data-value="1">Administrator</div></div></div></div></div></div> </div> <div class="actions"><div class="ui black deny button"> Cancel </div> <button class="ui submit red button" >Yes</button> </div></div> </form>');
          $('.ui.modal.user').modal('show');
          $('.ui.dropdown').dropdown('set selected', type);
          APP.modules.account.check_myaccount();
      }
       else if (action == 'modify_referent') {
        /*$.post("get_user_projects",{ mail_user: mail} ,function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });*/
       
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Modify informations: ' + mail);
          $(".modal.user .content").append('<form class="ui large form myaccount" method="post" action="/modifyuser"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"><input type="hidden" name="project_name_modify" value="' + project_name_modify + '"> <div class="ui error message"></div> <div class="field"><div class="ui selection dropdown"><input type="hidden" name="type"><i class="dropdown icon"></i><div class="default text">Access right</div><div class="menu"><div class="item" data-value="0">User</div><div class="item" data-value="3">User Feeder</div></div></div></div></div></div> </div> <div class="actions"><div class="ui black deny button"> Cancel </div> <button class="ui submit red button" >Yes</button> </div></div> </form>');
          $('.ui.modal.user').modal('show');
          $('.ui.dropdown').dropdown('set selected', type);
          APP.modules.account.check_myaccount();
      }
       else if (action == 'modify_referent_admin') {
        /*$.post("get_user_projects",{ mail_user: mail} ,function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });*/
       
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Modify informations: ' + mail);
          $(".modal.user .content").append('<form class="ui large form myaccount" method="post" action="/modifyuser"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="email" value="' + mail + '"><input type="hidden" name="project_name_modify" value="' + project_name_modify + '"> <div class="ui error message"></div> <div class="field"><div class="ui selection dropdown"><input type="hidden" name="type"><i class="dropdown icon"></i><div class="default text">Access right</div><div class="menu"><div class="item" data-value="0">User</div><div class="item" data-value="3">User Feeder</div><div class="item" data-value="2">Referent</div></div></div></div></div></div> </div> <div class="actions"><div class="ui black deny button"> Cancel </div> <button class="ui submit red button" >Yes</button> </div></div> </form>');
          $('.ui.modal.user').modal('show');
          $('.ui.dropdown').dropdown('set selected', type);
          APP.modules.account.check_myaccount();
      }
      else if (action == 'approve_project') {
          var action = "add_user_projects";
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Are you sure to approve ' + mail + ' to access to '+project_name+'?');
          $(".modal.user .content").append('<form class="ui form ' + action + ' " action="' + action + '" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="mail_user" value="' + mail + '"> <input type="hidden" name="project_name" value="' + project_name + '"><div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit button" >Yes</button> </div> </form>');
          $('.ui.modal.user').modal('show');
      } 
      else if (action == 'remove_project') {
          var action = "delete_user_projects";
          $(".modal.user .header").empty();
          $(".modal.user .content").empty();
          $(".modal.user .header").append('Are you sure to refuse ' + mail + ' to access to '+project_name+'?');
          $(".modal.user .content").append('<form class="ui form ' + action + ' " action="' + action + '" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '"><input type="hidden" name="mail_user" value="' + mail + '"> <input type="hidden" name="project_name" value="' + project_name + '"> <div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit button" >Yes</button> </div> </form>');
          $('.ui.modal.user').modal('show');
      } 
  },
  add_project: function(name_CSRF, value_CSRF) {
      $(".modal.user .header").empty();
      $(".modal.user .content").empty();
      $(".modal.user .header").append('Create a new project');
      $(".modal.user .content").append('<form class="ui form " action="/create_project" method="post"><input type="hidden" name="csrf_name" value="' + name_CSRF + '"><input type="hidden" name="csrf_value" value="' + value_CSRF + '">  <div class="field"> <div class="ui left icon input"> <i class="user icon"></i> <input type="text" name="project_name"  placeholder="Project name"> </div><div class="actions"> <div class="ui black deny button"> Cancel </div> <button class="ui submit green button" >Create</button> </div> </form>');
      $('.ui.modal.user').modal('show');
  },

   add_user: function(project_name) {
    $.post("add_user_projects",{ mail_user:$("input[type=text][name=usermail_add_"+project_name+"]").val(),project_name: project_name} ,function(data, status){
                   location.reload();

  })
},
   delete_user_from_project: function(mail_user,project_name) {

    $.post("delete_user_projects",{ mail_user:mail_user,project_name: project_name} ,function(data, status){
        if (data=1) {
            location.reload();
          /* $.post("get_user_in_projects",{ project_name: project_name} ,function(data, status){        
            APP.modules.account.display_project(project_name);
});*/
        }
  
        });
  }
}
})()


window.onload = (function() {
    APP.modules.map.init('map');
    APP.init();
    APP.modules.service.getallpoi();
})();
