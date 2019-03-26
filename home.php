<?php
/*
if(isset($_POST['submit'])) {
    if(isset($_POST['streetname']) & isset($_POST['housenumber'])& isset($_POST['from_node'])) {
        require_once 'config/config.php';
        require_once 'functions/func.php';

        $streetname = $_POST['streetname'];
        $housenumber = $_POST['housenumber'];
        $from_node = $_POST['from_node'];
        $to_node = 1877608520;
        $to_node = get_connected_node_from_addr($streetname, $housenumber);
        if($from_node & $to_node){
        //we can calculate route
            header('location: index.php?from='.$from_node.'&to='.$to_node);
        }
    }
}*/
if(False){}
else {

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>form</title>
        <link rel="stylesheet" href="site.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    </head>
    <body>
    <div id="warning_overlay" class="hidden">
        <div class="warning_message">
            <h1>This address does not exist. Please enter a valid address...</h1>
            <button onclick="hidewarning()">OK</button>
        </div>

    </div>
    <div class="wrapper">
        <form class="login" method="post" action="home.php">
            <p class="title">Pick up location</p>
            <span class="custom-dropdown big">
                <select name="from_node" id="from_node">
                    <option value="201245590">Kantienberg</option>
                </select>
          </span>
            <p class="title" style="margin-top: 30px">Destination</p>

            <p>Street</p>
            <input type="text" id="streetname" placeholder="Streetname" name="streetname"/>
            <i class="fa fa-road"></i>

            <p>Number</p>
            <input type="text" placeholder="Housenumber" id="housenumber" name="housenumber"/>
            <i class="fa fa-road"></i>

            <!--<a href="">No account? register here!</a>-->
            <button name="tempsubmit" id="tempsubmit">
                <i class="spinner"></i>
                <span class="state">Start</span>
            </button>
        </form>
    </div>
    <script src="script.js"></script>
    <script>
        var btn = document.getElementById("tempsubmit");
        btn.addEventListener("click", function(e){
            e.preventDefault();
           getres();
        });

        let from_node = document.getElementById("from_node").value;

        function getres(){
            let street = document.getElementById("streetname").value;
            let number = document.getElementById("housenumber").value;
            get_nodes_from_address(street, number, callback);
        }

        function callback(res){
                console.log("to"+res);
                console.log("from"+from_node);
                var isnum = /^\d+$/.test(res);
                if(isnum){
                    window.location.href = 'index.php?from='+from_node+'&to='+res;
                }
                else{
                    console.log("error");
                    $("#warning_overlay").removeClass("hidden");

                }
        }
        function hidewarning(){
            console.log("hello world");
            $("#warning_overlay").addClass("hidden");
        }
    </script>
    </body>
    </html>
    <?php
}
    ?>