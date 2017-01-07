/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function($)  {
    $(document).ready(function () {
         $('#myTable').DataTable({
          "pageLength": 50        
      });  // myTable
       $('.ncaoc_cases_results_headings').on('click', function () {
           console.log(event.target.id);

       });
    });
})(jQuery);


        
       


