(function($)  {
    $(document).ready(function () {
        var district;
  //      var server = 'http://pending_cases.dd:8083/';
        var server = 'https://jbtest4.nccourts.org/pending_cases/';
       $.getJSON(server+'ncaoc_pending_cases/api/county_district_map', function(data) {
            console.log(data);
            district = data;
        });
 
        $('#edit-query-fieldset-district-value').on('change', function () {
            var $countylist = $('#edit-query-fieldset-county-value'); 
            $countylist.empty();
            $.each(district[this.value], function(value,key) {
                $countylist.append($("<option></option>")
                .attr("value", key).text(key));
            });
        });
    });
})(jQuery);