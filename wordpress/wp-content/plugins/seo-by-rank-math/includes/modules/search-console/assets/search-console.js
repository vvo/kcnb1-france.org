/*!
 * Rank Math - Search Console
 *
 * @version 0.9.0
 * @author  Rank Math
 */
/*global moment google*/
;( function( $ ) {

	'use strict'

	// Document Ready
	$( function() {

		var RankMathSearchConsole = {
			init: function() {
				this.form = $( '.date-selector' )
				this.consoleFilters()
				this.dashboardCharts()
				this.keywordTracking()

				$( '.view-details' ).on( 'click', 'a', function( event ) {
					event.preventDefault()

					$( '.error-linked-urls.active' ).hide()

					$( this ).closest( '.column-primary' ).find( '.error-linked-urls' ).fadeIn().addClass( 'active' )
				})

				$( 'input', '.rank-math-sc-analytics .tablenav-pages' ).prop( 'disabled', true )
			},

			keywordTracking: function() {
				var form = $( '#rank-math-search-keywords' )
				if ( 0 === form.length ) {
					return
				}

				var self    = this,
					results = form.next( 'ul' ),
					list    = $( '#rank-math-keyword-list' )
				form.on( 'submit', function( event ) {
					event.preventDefault()
					rankMathAdmin.ajax( 'search_keywords', { q: form.find( '.regular-text' ).val() })
						.done( function( result ) {

							if ( result.success && result.keywords ) {
								results.html( '' )

								$.each( result.keywords, function( id, item ) {
									results.append( '<li>' + item.property + '<a href="#" data-id="' + item.property + '">Add</a></li>' )
								})
							}
						})
				})

				// Add
				results.on( 'click', 'a', function( event ) {
					event.preventDefault()

					var keyword = $( this ).data( 'id' )
					self.addKeyword( keyword, list )
				})

				form.on( 'click', '.add-keyword', function( event ) {
					event.preventDefault()

					var keyword = form.find( '.regular-text' ).val()
					self.addKeyword( keyword, list )
				})

				// Remove
				list.on( 'click', 'a', function( event ) {
					event.preventDefault()

					var link = $( this )
					rankMathAdmin.ajax( 'do_tracking_keyword', {
						what: 'remove',
						keyword: link.data( 'id' )
					}).done( function() {
						link.parent().remove()
					})
				})
			},

			addKeyword: function( keyword, list ) {
				rankMathAdmin.ajax( 'do_tracking_keyword', {
					what: 'add',
					keyword: keyword
				}).done( function( response ) {
					if ( response.success ) {
						list.append( '<li>' + keyword + '<a href="#" data-id="' + keyword + '">Remove</a></li>' )
					} else {
						rankMathAdmin.addNotice( response.error, 'error', $( '.wp-header-end' ), 2500 )
					}
				})
			},

			consoleFilters: function() {
				var self     = this,
					selector = $( '#rank-math-date-selector' )
				if ( 0 === selector.length ) {
					return
				}

				selector.dateRangePicker({
					format: 'YYYY-M-D',
					showShortcuts: true,
					time: false,
					shortcuts: {
						'prev-days': [ 7, 15, 30, 60, 90 ],
						'prev': [ 'month' ]
					},
					endDate: moment().subtract( 1, 'days' ).format( 'YYYY-M-D' )
				}).on( 'datepicker-change', function( event, obj ) {
					obj.date1.setHours( 0, 0, 0, 0 )
					obj.date2.setHours( 0, 0, 0, 0 )

					$( '#rank-math-start-date' ).val( obj.date1.getTime() / 1000 )
					$( '#rank-math-end-date' ).val( obj.date2.getTime() / 1000 )
					self.form.trigger( 'submit' )
				})

				$( '#rank-math-overview-type' ).on( 'change', function() {
					self.form.trigger( 'submit' )
				})

				$( '#rank-math-search' ).on( 'keyup', function( event ) {
					if ( 13 === event.keyCode ) {
						self.form.trigger( 'submit' )
					}
				})
			},

			dashboardCharts: function() {

				// Early Bail!!!
				if ( null === document.getElementById( 'analysis-overview-dashboard' ) ) {
					return
				}

				google.charts.load( 'current', { 'packages': [ 'corechart', 'controls' ] })
				google.charts.setOnLoadCallback( function() {

					var chartData = [ [ 'Date', 'Clicks', 'Impressions', 'Position', 'CTR' ] ]
					$.each( rankMath.overviewChartData, function() {
						chartData.push([ new Date( this.property ), parseInt( this.clicks ), parseInt( this.impressions ), parseFloat( this.position ), parseFloat( this.ctr ) ])
					})
					rankMath.overviewChartData = chartData

					if ( '' !== rankMath.overviewChartDataOld ) {
						var chartDataOld = [ [ 'Date', 'Clicks', 'Impressions', 'Position', 'CTR' ] ]
						$.each( rankMath.overviewChartDataOld, function() {
							chartDataOld.push([ new Date( this.property ), parseInt( this.clicks ), parseInt( this.impressions ), parseFloat( this.position ), parseFloat( this.ctr ) ])
						})
						rankMath.overviewChartDataOld = chartDataOld
					}


					var dataTable    = new google.visualization.arrayToDataTable( chartData ),
						dataTableOld = false,
						dashboard    = new google.visualization.Dashboard( document.getElementById( 'analysis-overview-dashboard' ) ),
						formatDate   = new google.visualization.DateFormat({ pattern: 'MMMM d, yyyy' })

					formatDate.format( dataTable, 0 )

					if ( '' !== rankMath.overviewChartDataOld ) {
						dataTableOld = new google.visualization.arrayToDataTable( rankMath.overviewChartDataOld )
						formatDate.format( dataTableOld, 0 )
					}

					var chartRangeFilter = new google.visualization.ControlWrapper({
						controlType: 'ChartRangeFilter',
						containerId: 'analysis-overview-filter',
						options: {
							filterColumnLabel: 'Date',
							ui: {
								chartType: 'AreaChart',
								chartOptions: {
									chartArea: {
										height: 60
									},
									hAxis: {
										baselineColor: 'none'
									}
								},
								minRangeSize: 86400000
							}
						}
					})

					var textStyle    = {
						color: '#999',
						fontName: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
						fontSize: 12
					},
					textStyle13 = $.extend({}, textStyle, { fontSize: 13 })
					var chartWrapper = new google.visualization.ChartWrapper({
						chartType: 'LineChart',
						containerId: 'analysis-overview-chart',
						options: {
							height: 500,
							hAxis: {
								gridlines: { color: 'transparent' },
								textStyle: textStyle
							},
							vAxis: { textStyle: textStyle },
							legend: { textStyle: textStyle13 },
							focusTarget: 'category'
						}
					})

					dashboard.bind( chartRangeFilter, chartWrapper )
					dashboard.draw( dataTable )

					// Default Options
					var defaultOptions = {
						colors: [ '#666', '#c1c1c1' ],
						height: 250,
						hAxis: {
							gridlines: { color: 'transparent' },
							textStyle: textStyle
						},
						vAxis: {
							textStyle: textStyle
						},
						legend: {
							position: 'none',
							textStyle: textStyle13
						},
						focusTarget: 'category'
					}

					var histories = { click: 1, impression: 2, ctr: 4, position: 3 }
					$.each( histories, function( metric, column ) {
						var elem = document.getElementById( 'analysis-overview-' + metric + '-history' )
						if ( null === elem ) {
							return true
						}

						var chart = new google.visualization.AreaChart( elem ),
							data = dataTable.clone()

						if ( 'click' === metric ) {
							data.removeColumns( 2, 3 )
						} else if ( 'impression' === metric ) {
							data.removeColumn( 1 )
							data.removeColumns( 2, 2 )
						} else if ( 'ctr' === metric ) {
							data.removeColumns( 2, 2 )
							data.removeColumn( 1 )
						} else if ( 'position' === metric ) {
							data.removeColumns( 1, 2 )
							data.removeColumns( 2, 3 )
						}

						if ( false !== dataTableOld && 0 < dataTableOld.getNumberOfRows() ) {
							data.addColumn( 'number', 'Previous' )
							for ( var i = 0; i < rankMath.overviewChartData.length - 1; i++ ) {
								data.setCell( i, 2, dataTableOld.getValue( i, column ) )
							}
						}

						chart.draw( data, defaultOptions )
					})
				})
			}
		}

		RankMathSearchConsole.init()
	})

}( jQuery ) )
