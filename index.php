<?php
/*
  author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
  created: oktober 2016
*/

session_start();

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
} else {
    $uid = $_SESSION["user_id"];
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    if (isset($_GET['from'])) $from = $_GET['from']; else $from = '3230007384';
    if (isset($_GET['to'])) $to = $_GET['to']; else $to = '1128905352';
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Demo</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css"/>
        <link rel="stylesheet" href="site.css">
        <script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
        <script src="js/math.min.js"></script>
        <script src="js/latlon-spherical.js"></script>
        <script src="script.js"></script>
        <style type="text/css">
            html, body {
                margin: 0px;
                padding: 0px;
                height: 100%;
                width: 100%;
            }

            div {
                font-family: Sans-serif;
            }

            #mapid {
                height: 100%;
            }

            h1, p {
                margin: 0px;
                padding: 5px 0px 5px 0px;
            }

            th {
                text-align: left;
            }

            tr {
                border-bottom: 1px solid lightgray
            }

            table {
                font-size: 0.8em;
            }
        </style>
        <!--
        Firefox fix:
        * go to about:config
        * change value of geo.wifi.uri from https://www.googleapis.com/geolocation/v1/geolocate?key=%GOOGLE_API_KEY% to https://location.services.mozilla.com/v1/geolocate?key=test
        -->

    </head>
    <body>
    <div style="height: 0%;">
        <div style="padding: 10px;">
            <div style="position: absolute; right: 10px; top: 10px"><a href="logout.php">Log out</a><br/><a
                        onclick="updateMap()" href="#">Refresh</a></div>
            <h1>Demo application</h1>
            <p>Location table:</p>
            <p id="demo" style="height: 100%; overflow: auto;"></p>
        </div>
    </div>

    <div style="height: 100%">
        <div id="mapid"></div>
    </div>

    <div class="float-button-wrapper">
        <button type="button" id="btnsubmit" class="btn btn-primary hidden">Delivered</button>
        <a style="text-decoration: none; color: white" href="after_delivery.php">
            <button type="button" id="btnfinish" class="btn btn-primary hidden">Finish</button>
        </a>

        <button type="button" id="btnstart" class="btn btn-primary hidden">Accept packets</button>
    </div>
    <div class="float-button-wrapper">
        <button type="button" id="btnmin" class="btn btn-primary hidden" style="bottom:  100px;">Remove a packet
        </button>
        <button type="button" id="btnplus" class="btn btn-primary hidden" style="bottom: 150px;">Add a packets</button>
    </div>
    <div class="float-info-wrapper">
        <ul>
            <li>distance: <span id="txt_afstand">5</span>km</li>
            <li>packets: <span id="txt_packets">1</span></li>
            <li>reward: €<span id="txt_reward">6</span></li>
        </ul>
    </div>
    <script>
        var x = document.getElementById("demo");
        var obj = [];

        var circle_me = 0;
        var polylines = [];
        var lat = 0;
        var lon = 0;
        var acc = 0;
        var from_node = <?php echo $from;?>;
        var to_node = <?php echo $to;?>;

        var count = 0;

        function permute(permutations, pre, cur) {
            var len = cur.length;
            for (var i = 0; i < len; i++) {
                var p = clone(pre);
                var c = clone(cur);
                p.push(cur[i]);
                remove(c, cur[i]);
                if (len > 1) {
                    permute(permutations, p, c);
                } else {
                    permutations.push(p);
                    //console.log(p);
                    count++;
                }
            }
        }

        function print(arr) {
            var len = arr.length;
            for (var i = 0; i < len; i++) {
                document.write(arr[i] + " ");
            }
            document.write("<br />");
        }

        function remove(arr, item) {
            if (contains(arr, item)) {
                var len = arr.length;
                for (var i = len - 1; i >= 0; i--) { // STEP 1
                    if (arr[i] == item) {             // STEP 2
                        arr.splice(i, 1);              // STEP 3
                    }
                }
                //$("#txt_packets").text(len);
            }
        }

        function contains(arr, value) {
            for (var i = 0; i < arr.length; i++) {
                if (arr[i] == value) {
                    return true;
                }
            }
            return false;
        }

        function clone(arr) {
            var a = new Array();
            var len = arr.length;
            for (var i = 0; i < len; i++) {
                a.push(arr[i]);
            }
            return a;
        }

        function updateLocation() {
            //console.log("updateLocation()");
            //console.log("user_id=" + <?php echo $_SESSION["user_id"];?> + "&lat="+ lat +"&lon=" + lon + "&acc=" + acc);
            // update position in MySQL database
            var timestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
            $.ajax({
                url: 'share_geolocation.php',
                type: "POST",
                data: "user_id=" + <?php echo $_SESSION["user_id"];?> +"&lat=" + lat + "&lon=" + lon + "&acc=" + acc,
                success: function (data) {
                    //console.log(data);
                    mymap.removeLayer(markers_other_people);
                    markers_other_people.clearLayers();
                    //if(acc > 65) acc_ = 10; else acc_ = acc;
                    acc_ = 10;
                    if (circle_me != 0) mymap.removeLayer(circle_me);
                    circle_me = L.circle([lat, lon], {
                        color: 'green',
                        fillColor: 'green',
                        fillOpacity: 0,
                        radius: acc_
                    }).addTo(mymap).bindPopup("<p style='margin: 0px;'>You!</p><table><tr><th>User_id</th><td>" + <?php echo $_SESSION["user_id"];?> +"</td></tr><tr><th>Location</th><td>(" + lat + "," + lon + ")</td></table>");
                    try {
                        obj = data;//JSON.parse(data);
                    } catch (e) {
                        obj = {}
                        console.log("Error parsing JSON geolocation");
                    }

                    usr_table = "<table><tr><th>user_id</th><th>lat</th><th>lon</th><th>acc</th><th>timestamp</th></tr>";
                    for (var i = 0; i < obj.length; i++) {
                        if (obj[i][0] == <?php echo $_SESSION["user_id"];?>) {
                            usr_table += "<tr style='color: green'><td>" + obj[i][0] + "</td><td>" + obj[i][1] + "</td><td>" + obj[i][2] + "</td><td>" + obj[i][3] + "</td><td>" + obj[i][4] + "</td></tr>";
                        }
                        /* else{
                             usr_table += "<tr><td>" + obj[i][0] + "</td><td>" + obj[i][1] + "</td><td>" + obj[i][2] + "</td><td>" + obj[i][3] + "</td><td>" + obj[i][4] + "</td></tr>";
                             if(obj[i][3] > 65) acc_ = 65; else acc_ = obj[i][3];
                             var marker = L.circle([obj[i][1], obj[i][2]], {
                                 color: 'blue',
                                 fillColor: 'lightblue',
                                 fillOpacity: 0.5,
                                 radius: acc_
                             }).bindPopup("<table><tr><th>User_id</th><td>" + obj[i][0] + "</td></tr><tr><th>Location</th><td>(" + obj[i][1] + "," + obj[i][2] + ")</td></table>");
                             markers_other_people.addLayer(marker);
                         }*/
                    }
                    mymap.addLayer(markers_other_people);
                    usr_table += "</table>"
                    x.innerHTML = usr_table;


                    /*
                    INSERT ANY OTHER CODE WHICH NEEDS TO RUN ON LOCATION UPDATE HERE
                    */
                },
                error: function (xhr, status, errorThrown) {
                    console.log("Error: " + errorThrown);
                    console.log("Status: " + status);
                    console.dir(xhr);
                }
            });
        }

        function travelingSalesman() {
            console.log("travelingSalesman()");

            console.log(obj.length);

            if (obj.length > 0) {

                var weights = math.zeros(obj.length, obj.length);
                var usr_ids = [];
                var real_usr_ids = [];
                for (var i = 0; i < obj.length; i++) {
                    usr_ids.push(i);
                    real_usr_ids.push(obj[i][0]);
                    for (var j = i + 1; j < obj.length; j++) {
                        if (i != j) {
                            var pos_i = new LatLon(parseFloat(obj[i][1]), parseFloat(obj[i][2]));
                            var pos_j = new LatLon(parseFloat(obj[j][1]), parseFloat(obj[j][2]));
                            var dst = pos_i.distanceTo(pos_j);
                            weights.subset(math.index(i, j), dst);
                            weights.subset(math.index(j, i), dst);
                            console.log(i + " " + j + " " + dst);
                        }
                    }
                }
                console.log(weights);

                // BRUTE FORCE
                var permutations = [];
                permute(permutations, [], usr_ids);
                console.log("Permutations: ");
                console.log(permutations);

                var solution;
                var distance = -1;
                for (var i = 0; i < permutations.length; i++) {
                    total_dist = 0;
                    //only consider starting points to be the own user id

                    if (real_usr_ids[permutations[i][0]] == <?php echo $_SESSION["user_id"];?>) {
                        console.log('id: ' + permutations[i][0].toString());
                        from_id = permutations[i][0];
                        for (var j = 0; j < permutations[i].length; j++) {
                            to_id = permutations[i][j];
                            total_dist += weights.subset(math.index(from_id, to_id));
                            from_id = permutations[i][j];
                        }
                        if (distance < 0 || total_dist < distance) {
                            solution = permutations[i];
                            distance = total_dist;
                        }
                    }
                    //console.log("total distance");
                    //console.log(total_dist);
                }

                console.log("solution");
                console.log(solution);

                for (var i = 0; i < polylines.length; i++) {
                    mymap.removeLayer(polylines[i]);
                }
                // draw lines in the right order
                for (var i = 0; i < solution.length - 1; i++) {
                    console.log(parseFloat(obj[solution[i]][1]));
                    console.log(parseFloat(obj[solution[i]][2]));
                    console.log(parseFloat(obj[solution[i + 1]][1]));
                    console.log(parseFloat(obj[solution[i + 1]][2]));
                    var p1 = new L.LatLng(parseFloat(obj[solution[i]][1]), parseFloat(obj[solution[i]][2]));
                    var p2 = new L.LatLng(parseFloat(obj[solution[i + 1]][1]), parseFloat(obj[solution[i + 1]][2]));
                    var pointList = [p1, p2];
                    polylines.push(new L.polyline(pointList, {
                        color: 'yellow',
                        weight: 10,
                        opacity: 0.5,
                        smoothFactor: 1
                    }).addTo(mymap));
                }
                setTimeout(function () {
                    travelingSalesman();
                }, 5000);
            }
        }

        function updateMap() {
            //console.log("updateMap()");
            //x.innerHTML="Latitude: " + lat + "<br>Longitude: " + lon;

            updateLocation();

        }

        function buttonchecker(lat, lon) {
            var should_be_hidden = true;
            let id = -1;
            for (var i = 0; i < packets.length; i++) {
                if (Math.abs(packets[i].lat - lat) < 0.1005 && Math.abs(packets[i].lon - lon < 0.1005)) {
                    should_be_hidden = false;
                    id = packets[i].id;
                    break;
                }
            }
            if (should_be_hidden) {
                //console.log("hide button")
                $("#btnsubmit").addClass("hidden");
            }
            else {
                //console.log("hide button");
                $("#btnsubmit").removeClass("hidden").on("click", function () {
                    update_single_packet_status(id, "delivered", <?php echo $uid ?>);
                });

            }
        }


        function showPosition(position) {
            //console.log('showPosition()');
            lat = position.coords.latitude;
            lon = position.coords.longitude;
            acc = position.coords.accuracy;

            //check if deliverd button should be showed.

            buttonchecker(lat, lon);
            //console.log("packets at pos");
            //console.log(packets)
            updateMap();
        }

        function errorPosition(err) {
            console.warn('ERROR(' + err.code + '):' + err.message);
        }

        function getLocation() {
            if (navigator.geolocation) {
                //console.log('Geolocation enabled');
                navigator.geolocation.watchPosition(showPosition, errorPosition);
            } else {
                x.innerHTML = "Geolocation is not supported by this browser.";
            }
            setTimeout(getLocation, 5000);

        }

        getShortestPath(<?php echo $from . ',' . $to ?>);

        $("#btnstart").on("click", function () {
            //console.log("accepted number of packets " + packets.length);
            $("#btnstart").addClass("hidden");
            $("#btnplus").addClass("hidden");
            $("#btnmin").addClass("hidden");
            plot_packets(packets, []);
            all_packets = packets;
            for (var i = 0; i < packets.length; i++) {
                update_single_packet_status(packets[i].id, "busy", <?php echo $uid ?>)
            }
            getLocation();
        });
        $("#btnplus").on("click", function () {
            if (N < all_packets.length) {
                N += 1;
                packets = getPacketsPath(all_packets, N);
                $("#btnstart").addClass("hidden");
                $("#btnplus").addClass("hidden");
                $("#btnmin").addClass("hidden");
                //$("#txt_packets").text(N);

            }
        });
        $("#btnmin").on("click", function () {
            if (N > 1) {
                N -= 1;
                packets = getPacketsPath(all_packets, N);
                $("#btnstart").addClass("hidden");
                $("#btnplus").addClass("hidden");
                $("#btnmin").addClass("hidden");
                //$("#txt_packets").text(N);

            }
        });
        var packets = [];
        var all_packets = [];
        var N = 5;


        getpacket_coordinates(<?php echo $from . ',' . $to . ',' . $uid ?>, function (arr) {
            all_packets = arr;
            if (!all_packets.length) {
                window.location.href = "no_packets.php";
            }
            N = Math.min(N,all_packets.length);
            packets = getPacketsPath(all_packets, N);
            $("#btnstart").removeClass("hidden");
            $("#btnplus").removeClass("hidden");
            $("#btnmin").removeClass("hidden");
            //$("#txt_packets").text(packets.length);

        });

        var mymap = L.map('mapid').setView([51.041510, 3.728726], 14);
        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: 'pk.eyJ1IjoibXNsZW1icm8iLCJhIjoiY2l0anp0Z2FkMDAzcjN4bGlxemFzczcwNyJ9.0P3QRRthdL8Jf2pGdWWI3g'
        }).addTo(mymap);
        var markers_other_people = new L.FeatureGroup();
        //setTimeout(function(){travelingSalesman();}, 5000);
    </script>

    </body>
    </html>
<?php } ?>