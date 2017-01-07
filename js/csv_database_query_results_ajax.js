/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function($)  {
    $(document).ready(function () {
         $('#myTable').DataTable({
             serverSide: true,
             ajax: {
                 url: '/ncaoc_pending_cases/api/query',
                 type: 'POST'
             },
             "columns": [
                { "data": "District" , title: "District"},
                { "data": "COUNTY",  title: "County"}, 
                { "data": "CASE_NUMBER", title: "Case Number"  },
                { "data": "DEFENDANT_NAME", title: "Defendant Name"  }
        ],
        "start": 100,
        "length": 20,
     //   dom: 'Bfrtip',
     //   "dom": 'Bfr<"H"lf><"datatable-scroll"t><"F"ip>', 
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]       
      });  // myTable
       $('.ncaoc_cases_results_headings').on('click', function () {
           console.log(event.target.id);

       });
    });
})(jQuery);


        
       


