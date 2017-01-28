<canvas id="chart-details"></canvas>

<script>
jQuery(function($) {



// For Line chart

var lineData = {
		    labels: [<?php echo $str_date ?>],
		    datasets: [
		        {
		            label: "<?php _e('Activity', 'cpm') ; ?>",
		            fillColor: "rgba(120,200, 223, 0.4)",
		            strokeColor: "#79C7DF",
		            pointColor: "#79C7DF",
		            pointStrokeColor: "#79C7DF",
		            pointHighlightFill: "#79C7DF",
		            pointHighlightStroke: "#79C7DF",
		            scaleLabel: "Test <%=value%>",
		            data: [<?php echo $str_activity ?>]
		        },
		        {
		            label: "<?php _e('Assign Task', 'cpm') ?>",
		            fillColor: "rgba(185, 114, 182,0.5)",
		            strokeColor: "#B972B6",
		            pointColor: "#B972B6",
		            pointStrokeColor: "#B972B6",
		            pointHighlightFill: "#B972B6",
		            pointHighlightStroke: "rgba(151,187,205,1)",
		            data: [<?php echo $str_task ?>]
		        },
		        {
		            label: "<?php _e('Complete Task', 'cpm') ?>",
		            fillColor: "rgba(34,139,34, 0.5)",
		            strokeColor: "#397D02",
		            pointColor: "#397D02",
		            pointStrokeColor: "#397D02",
		            pointHighlightFill: "#397D02",
		            pointHighlightStroke: "rgba(151,187,205,1)",
		            data: [<?php echo $str_ctask ?>]
		        }
		    ]
		};


		Chart.defaults.global.responsive = true;
		var ctxl = $("#chart-details").get(0).getContext("2d");
                    ctxl.canvas.height = jQuery(".cpm-mytask-chart-overview").height()-102;

		// This will get the first returned node in the jQuery collection.
		var cpmChart = new Chart(ctxl).Line(lineData, {
			pointDotRadius : 8,
			animationSteps: 60,
			tooltipTemplate: "<%=label%>:<%= value %>",
			animationEasing: "easeOutQuart",
                        responsive: true,
                        maintainAspectRatio: false

		});



});
</script>
