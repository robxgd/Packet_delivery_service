/*
var working = false;
$('.login').on('submit', function(e) {
    e.preventDefault();
    if (working) return;
    working = true;
    var $this = $(this),
        $state = $this.find('button > .state');
    $this.addClass('loading');
    $state.html('Authenticating');
    setTimeout(function() {
        $this.addClass('ok');
        $state.html('Welcome back!');
        setTimeout(function() {
            $state.html('Log in');
            $this.removeClass('ok loading');
            working = false;
        }, 4000);
    }, 3000);
});

*/

var pack_markers = [];
var pack_route = null;
var normal_route = null;

var packetIcon = L.icon({
    iconUrl: 'packet.png',
    //shadowUrl: 'leaf-shadow.png',

    iconSize: [24, 24], // size of the icon
    //shadowSize:   [50, 64], // size of the shadow
    //iconAnchor:   [22, 94], // point of the icon which will correspond to marker's location
    //shadowAnchor: [4, 62],  // the same for the shadow
    //popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
});
var greenIcon = new L.Icon({
    iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
var redIcon = new L.Icon({
    iconUrl: 'checkered-flag.png',
//    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [30, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
function getShortestPath(from, to, color='red') {
    var xhttp = new XMLHttpRequest();
    //console.log('route readyState: ' + this.readyState + ' status: ' + this.status);
    xhttp.onreadystatechange = function () { //TODO: fix status = 500?
        if (this.readyState == 4 && (this.status == 200 || this.status == 500)) {
            //document.getElementById("demo").innerHTML = this.responseText;
            if(this.response.indexOf("error")!=-1){
                console.log(this.response)
            }
            else{
                normal_route = plot_route(this.response, color, normal_route);
            }
        }
    };
    xhttp.open("GET", "API/shortest_route.php?from_node=" + from + "&to_node=" + to + "", true);
    xhttp.send();
}

function getPacketsPath(pack, aantal = 5) {
    var to_deliver = pack.slice(0, aantal);
    var others = pack.slice(aantal, pack.length);
    plot_packets(to_deliver, others);
    to_deliver.sort(packetsort_on_segment);
    get_route_from_packets(to_deliver);
    return to_deliver
}

function get_route_from_packets(packets) {
    var nodes = [];
    nodes.push(from_node);
    for (let i = 0; i < packets.length; i++) {
        nodes.push(packets[i].node);
    }
    nodes.push(to_node);

    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        //console.log("route from packets readyState " + this.readyState + " status: " + this.status);
        if (this.readyState == 4 && (this.status == 200 || this.status == 500)) { //TODO: FIXME STATUS 500
            //let route = JSON.parse(this.response).path_geo;
            //route = route.path_geo;
            //console.log(this.response);
            if(this.response.indexOf("error")!=-1){
                console.log(this.response)
            }
            else{
                pack_route = plot_route(this.response, color = 'blue', pack_route);
            }
            $("#btnstart").removeClass("hidden");
            $("#btnplus").removeClass("hidden");
            $("#btnmin").removeClass("hidden");
        }
    };
    xhttp.open("GET", "API/shortest_route_array.php?nodes=" + nodes, true);
    xhttp.send();
}

function packetsort_on_segment(a, b) {
    if (a.closest_streetsegment < b.closest_streetsegment) {
        return -1;
    }
    if (a.closest_streetsegment > b.closest_streetsegment) {
        return 1;
    }
    if(a.u < b.u){
        return -1;
    }
    if(a.u > b.u){
        return 1;
    }
    if(a.dist_to_streetsegment < b.dist_to_streetsegment){
        return -1;
    }
    if(a.dist_to_streetsegment > b.dist_to_streetsegment){
        return 1;
    }
    return 0;
}


function plot_route(obj, col = 'red', polyLine = null) {
    if (polyLine != null) {
        mymap.removeLayer(polyLine);
    }
    //show route from obj of route
    let the_object = JSON.parse(obj);
    //update distance in ui
    //console.log(packets);
    let distance = the_object.distance/1000
    $("#txt_afstand").text(distance.toPrecision(4));
    $("#txt_packets").text(packets.length);
    reward_for_dist = distance.toPrecision(4)/10;
    let reward = reward_for_dist + packets.length;
    $("#txt_reward").text(reward);
    let latlngs = the_object.path_geo;
    L.marker({"lat": latlngs[0][0], "lon": latlngs[0][1]}, {icon: greenIcon}).addTo(mymap);
    L.marker({"lat": latlngs[latlngs.length-1][0], "lon": latlngs[latlngs.length-1][1]}, {icon: redIcon}).addTo(mymap);
    return L.polyline(latlngs, {color: col}).addTo(mymap);

}

function plot_packets(for_delivery, others) {
    //console.log(for_delivery);
    for (let i = 0; i < pack_markers.length; i++) {
        mymap.removeLayer(pack_markers[i]);
    }
    pack_markers = [];
    for (let i = 0; i < for_delivery.length; i++) {
        let p = for_delivery[i];
        let m = L.marker({"lat": p['lat'], "lon": p['lon']});
        m.addTo(mymap);
        pack_markers.push(m);
    }
    for (let i = 0; i < others.length; i++) {
        let p = others[i];
        if (p['lat'] & p['lon']) {
            let m = L.marker({"lat": p['lat'], "lon": p['lon']}, {icon: packetIcon});
            m.addTo(mymap);
            pack_markers.push(m);
        }
    }
}

function update_packet_status(pack_ids) {
    var xhttp2 = new XMLHttpRequest();

    xhttp2.onreadystatechange = function () {
        if (this.readyState == 4 && (this.status == 200||this.status==500)) {
            for(let i=0;i<packets.length;i++){
            }
        }
    };
    xhttp2.open("GET", "API/update_packets_status.php?packet=" + pack_ids, true);
    xhttp2.send();
}

function update_single_packet_status(pack_id, status, uid) {
    $("#btnsubmit").addClass("hidden");
    var xhttp2 = new XMLHttpRequest();

    if(status =='delivered'){
        //console.log("delivery!" + pack_id);
        xhttp2.onreadystatechange = function () {
            if (this.readyState == 4 && (this.status == 200||this.status==500)) {
                for(let i=0;i<packets.length;i++){
                    if(packets[i].id == this.response){
                        packets.splice(i,1);
                    }
                }
                if(packets.length==0){
                    $("#btnfinish").removeClass("hidden");
                }
                plot_packets(packets,[]);
                buttonchecker(lat, lon);
            }
        };
    }
    else{
        xhttp2.onreadystatechange = function (){};
    }
    xhttp2.open("GET", "API/update_packets_status.php?packet=" + pack_id + "&status=" + status+"&uid="+uid, true);
    xhttp2.send();
}

function getpacket_coordinates(from, to, uid, callback) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        //console.log('getpacket readyState: ' + this.readyState + ' status: ' + this.status);
        if (this.readyState == 4 && (this.status == 200 || this.status == 500)) { //TODO: FIXME STATUS 500

            if(this.response.indexOf("error")!=-1){
                console.log(this.response)
            }
            //packets are sorted on distance from route in backend
            let all_the_packets = Object.values(JSON.parse(this.response));
            //plot_packets([], all_the_packets);
            callback(all_the_packets);
        }
    };
    xhttp.open("GET", "API/get_packets_in_box.php?from_node=" + from + "&to_node=" + to + "&uid="+uid, true);
    xhttp.send();
}

function get_nodes_from_address(street, number, callback){
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && (this.status == 200 || this.status == 500)) { //TODO: FIXME STATUS 500
            let tonode =this.response;
            callback(tonode);
        }
    };
    xhttp.open("GET", "API/get_node_from_adress.php?streetname=" + street + "&housenumber=" + number + "", true);
    xhttp.send();
}

