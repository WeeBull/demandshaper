<?php 
/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

global $path;

$device = "";
if (isset($_GET['node'])) $device = $_GET['node'];

$v=9;

$emoncmspath = $path;
if ($remoteaccess) $emoncmspath .= "remoteaccess/";

?>

<script>
var path = "<?php echo $path; ?>";
var emoncmspath = "<?php echo $emoncmspath; ?>";
var device = "<?php echo $device; ?>";
var devices = {};

var apikeystr = "";
if (window.session!=undefined) {
    apikeystr = "&apikey="+session["apikey_write"];
}
</script>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.touch.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/common/vis.helper.js"></script>

<link rel="stylesheet" href="<?php echo $path; ?>Modules/demandshaper/demandshaper.css?v=<?php echo $v; ?>">
<script type="text/javascript" src="<?php echo $path; ?>Modules/demandshaper/battery.js?v=<?php echo $v; ?>"></script>

  <div id="scheduler-top"></div>
  
  <div id="auth-check" class="hide">
      <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect 
      <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
  </div>

  <div id="no-devices-found" class="hide">

      <h2 id="no-devices-found-title">No Devices Found</h2>
            
      <div style="display:inline-block; padding:10px"><span class='icon-smartplug'></span></div>
      <div style="display:inline-block; padding:10px"><span class='icon-hpmon'></span></div>
      <div style="display:inline-block; padding:10px"><span class='icon-openevse'></span></div>
      <div style="height:10px"></div>

      <div id="no-devices-found-checking">
          <p>Checking for pairing request</p><br>
          <img src="<?php echo $path; ?>Modules/demandshaper/ajax-loader.gif">
          <!--
          <br><br>
          <p>1. Plug your smart plug into an electrical socket. The light on the plug will show green for 3 seconds followed by a short off period and then a couple of very short flashes. This indicates that the plug is working and has created a WIFI Access Point.</p>
          <p>2. The WIFI Access Point should appear in your laptop or phones available WIFI networks, the SSID will contain the name smartplug followed by a number e.g: 'smartplug1'.</p>
          <p>3. Connect to this network, open an internet browser and enter the following address:</p>
          <p>http://192.168.4.1</p>
          -->
      </div>
  </div>

  <?php
      if (strpos($device,"emonth")!==false) {
          include "Modules/demandshaper/emonth.php";
      } else {
          include "Modules/demandshaper/general.php";
      }
  ?>

  <script>
  init_sidebar({menu_element:"#demandshaper_menu"});
  
  device_loaded = false;
  
  update_sidebar();
  setInterval(update_sidebar,10000);
  function update_sidebar() {
      $.ajax({ url: emoncmspath+"device/list.json", dataType: 'json', async: true, success: function(result) {
          // Associative array of devices by nodeid
          devices = {};
          var out = "";
          for (var z in result) {
              if (result[z].type=="openevse" || result[z].type=="smartplug" || result[z].type=="hpmon") {
                  devices[result[z].nodeid] = result[z];
                  // sidebar list
                  out += "<li><a href='"+path+"demandshaper?node="+result[z].nodeid+"'><span class='icon-"+result[z].type+"'></span>"+ucfirst(result[z].nodeid)+"</a></li>";
                  // select first device if device is not defined
                  if (device=="") device = result[z].nodeid;
              }
          }
          n=0
          for (var z in result) {
              if (result[z].type=="emonth") {
                  devices[result[z].nodeid] = result[z];
                  // sidebar list
                  border = "";
                  if (n==0) border = "style='border-top:1px solid #aaa'";
                  out += "<li "+border+"><a href='"+path+"demandshaper?node="+result[z].nodeid+"'><span class='icon-"+result[z].type+"'></span>"+ucfirst(result[z].nodeid)+"</a></li>";
                  // select first device if device is not defined
                  if (device=="") device = result[z].nodeid;
                  
                  n++
              }
          }
          
          out += "<li id='add-device' style='border-top:1px solid #aaa; cursor:pointer'><a><i class='icon-plus icon-white'></i> Add Device</a></li>";
          
          $(".sidenav-menu").html(out);
          if (!device_loaded) {
              if (device!=undefined && devices[device]!=undefined) {
                  hide_device_finder();
                  load_device();
              } else {
                  show_device_finder();
              }
          }
      }});
  }

  function ucfirst(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
  }
  </script>

<script>
var emoncmspath = "<?php echo $emoncmspath; ?>";
// -------------------------------------------------------------------------------------------------------
// Device authentication transfer
// -------------------------------------------------------------------------------------------------------
var auth_check_interval = false;
function auth_check(){
    $.ajax({ url: emoncmspath+"device/authcheck.json", dataType: 'json', async: true, success: function(data) {
        if (typeof data.ip !== "undefined") {
            $("#auth-check-ip").html(data.ip);
            $("#auth-check").show();
            $("#table").css("margin-top","0");
            $("#no-devices-found-title").html("Device Found");
            $("#no-devices-found-checking").html("Click Allow to pair device");
        } else {
            $("#table").css("margin-top","3rem");
            $("#auth-check").hide();
        }
    }});
}

$(".auth-check-allow").click(function(){
    var ip = $("#auth-check-ip").html();
    $.ajax({ url: emoncmspath+"device/authallow.json?ip="+ip, dataType: 'json', async: true, success: function(data) {
        $("#auth-check").hide();
        $("#no-devices-found-checking").html("Please wait for device to connect");
    }});
});

$(".sidenav-menu").on("click","#add-device",function(){
    show_device_finder();
});

function show_device_finder() {
    $("#no-devices-found").show();
    $("#scheduler-outer").hide();
    auth_check();
    clearInterval(auth_check_interval);
    auth_check_interval = setInterval(auth_check,5000);
}

function hide_device_finder() {
    $("#no-devices-found").hide();
    clearInterval(auth_check_interval);
}

</script>
