var APP = (function() {
    return {
        modules: {},
        group: null,
        init: function() {

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
    var map, markers;

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

            //map.on('click', APP.modules.affichage.closePanel);
        },

        /**
         * methode d'affichage
         * @param data
         */
        affichagePoi: function(data, all, updatedate, updatemesure) {
            data = JSON.parse(data);
            $('.message').empty();
            if (data == null || data.length == 0) {
                $('.message').append('<div class="ui container"><div class="column"><div class="ui negative message">  <div class="header"> No data found </div> <p>Please try again later </p></div></div></div>');
            } else {
                if (APP.group != null) {

                    APP.group.clearLayers();
                }
                var markers = []
                markers._popup = "";
                markers._popup._content = ""
                var icon = 'station-icon.png';
                var icon2x = 'station-icon-2x.png';
                var stationIcon = L.icon({
                    iconUrl: 'js/images/' + icon,
                    iconRetinaUrl: 'js/images/' + icon2x,
                    iconSize: [25, 41], // size of the icon
                    iconAnchor: [12, 40]
                });
                var array = $.map(data, function(value, index) {
                    return [value];
                });
                var lithology = [];
                var creationdate = [];
                lithology['all'] = 'all';
                var measurement_abbreviation = [];
                array.forEach(function(k, v) {
                    lithology[k.SUPPLEMENTARY_FIELDS.LITHOLOGY] = (k.SUPPLEMENTARY_FIELDS.LITHOLOGY);
                    creationdate.push(k.SAMPLING_DATE[0]);
                    k.MEASUREMENT.forEach(function(k, v) {
                        measurement_abbreviation[k[0].ABBREVIATION] = (k[0].ABBREVIATION);
                    });

                    var long = k.SAMPLING_POINT[0].LONGITUDE.replace(/\s+/g, '');
                    var lat = k.SAMPLING_POINT[0].LATITUDE.replace(/\s+/g, '');
                    var firstProj = '+proj=lcc +lat_1=49 +lat_2=44 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';
                    var secondProj = '+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs ';
                    var latlng = proj4(firstProj, secondProj, [k.LONGITUDE, k.LATITUDE]);
                    var marker = L.marker([long, lat]);
                    marker.bindPopup(k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME);
                    marker.on('click', function(e) {
                        measurements = '';
                        k.MEASUREMENT.forEach(function(k, v) {
                            measurement = ' <div class="item measurement_abbreviation" value="' + k[0].ABBREVIATION + '"> <div class="content"> <div class="header">' + k[0].ABBREVIATION + '</div> </div> </div>'
                            measurements += measurement;
                        });
                        measurements = '<div class="ui middle aligned selection list">' + measurements + '</div>'
                        setTimeout(function() {
                            $('.ui.sidebar.right').sidebar('setting', 'transition', 'overlay').sidebar('show');
                        }, 50);
                        setTimeout(function() {
                            $('.pusher').removeClass('dimmed');
                        }, 200);
                        $('.ui.sidebar.right').empty();
                        $('.ui.sidebar.right').append('<div class="ui styled accordion"> <div class="active title"> <i class="dropdown icon"></i> ' + k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME + ' </div> <div class="active content"> <h3>' + k.TITLE.substr(0, k.TITLE.lastIndexOf("_")) + '</h3><p> Description: ' + k.SUPPLEMENTARY_FIELDS.DESCRIPTION + '<br> Sample Name: ' + k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME + '<br> Alteration degree: ' + k.SUPPLEMENTARY_FIELDS.ALTERATION_DEGREE + '<br> Lithology: ' + k.SUPPLEMENTARY_FIELDS.LITHOLOGY + '<br> Direction1: ' + k.SUPPLEMENTARY_FIELDS.DIRECTION1 + '<br> Direction2: ' + k.SUPPLEMENTARY_FIELDS.DIRECTION2 + '<br> Direction3: ' + k.SUPPLEMENTARY_FIELDS.DIRECTION3 + '<br> Latitude: ' + k.SAMPLING_POINT[0].LATITUDE + ' Longitude: ' + k.SAMPLING_POINT[0].LONGITUDE + '</p></div> <div class="title"> <i class="dropdown icon"></i> View data </div> <div class="content">' + measurements + ' </div></div>')
                        $('.ui.accordion').accordion();
                        $('.item.measurement_abbreviation').on('click', function(e) {
                            mesure = $(this).text();
                            mesure = mesure.replace("/ /g", "");
                            name = k.SUPPLEMENTARY_FIELDS.SAMPLE_NAME + "_" + mesure;
                            name = name.replace("/ /g", "");
                            $("#preview").empty();
                            $("#preview").append('<iframe src="/Backend/src/index.php/preview_poi_data/' + name + '" style="width:100%; height:550px;" frameborder="0"></iframe>');
                            $(".actions a").remove();
                            $(".actions .download").remove();
                            $(".actions").append(' <a href="/Backend/src/index.php/download_poi_data/' + name + '"><div class="ui green  button">Download</div></a>')
                            $('.ui.modal.preview').modal('show');

                        });
                    });



                    marker.on('mouseover', function(e) {
                        this.openPopup();
                    });
                    marker.on('mouseout', function(e) {
                        this.closePopup();
                    });
                    markers.push(marker);
                });
                append = "";
                var minDate = creationdate.reduce(function(a, b) {
                    return a < b ? a : b;
                });
                var maxDate = creationdate.reduce(function(a, b) {
                    return a > b ? a : b;
                });
                for (key in measurement_abbreviation) {
                    item = '<div class="item">' + key + '</div>';
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
                });


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




                APP.group = L.featureGroup(markers); // on met le groupe de markers dans une layer
                APP.group.getLayers().length;
                APP.group.addTo(map);
                bounds = APP.group.getBounds();
                map.fitBounds(bounds);

            }

        },




    }
})();

APP.modules.service = (function() {

    return {
        getallpoi: function() {
            $.get("/Backend/src/index.php/get_all_poi", function(data) {
                APP.modules.map.affichagePoi(data, true, true, true);
            });
        },
        getpoisorted: function(json, updatedate, updatemesure) {
            $.post("/Backend/src/index.php/get_poi_sort", {
                json: json
            }, function(data) {
                APP.modules.map.affichagePoi(data, false, updatedate, updatemesure);
            });
        },
        getdata: function(json) {
            $.post("/Backend/src/index.php/get_poi_type_data", {
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
            $.post("/Backend/src/index.php/download_poi_type_data", {
                json: json
            }, function(data) {
                var pom = document.createElement('a');
                pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data));
                json = JSON.parse(json);
                pom.setAttribute('download', json.mesure+'_'+json.lithology+'_'+json.mindate+'_'+json.maxdate+ '.csv');

                if (document.createEvent) {
                    var event = document.createEvent('MouseEvents');
                    event.initEvent('click', true, true);
                    pom.dispatchEvent(event);
                } else {
                    pom.click();

                }

            });
        },
        searchlithologyanddateandmesure: function(lithology, mesure, mindate, maxdate) {
            obj = {
                "lithology": lithology,
                'mesure': mesure,
                "mindate": mindate,
                "maxdate": maxdate
            };
            json = JSON.stringify(obj);
            APP.modules.service.getpoisorted(json, false, false);
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


window.onload = (function() {
    APP.modules.map.init('map');
    APP.init();
    APP.modules.service.getallpoi();
})();