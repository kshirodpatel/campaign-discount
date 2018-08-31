jQuery(document).ready(function($) {

  var www = Wistia.api("xv7loe1wfi");
  console.log("I got a handle to the video!", www);

  www.bind("end", function() {
    console.log("Lenny was here.");
  });

});
