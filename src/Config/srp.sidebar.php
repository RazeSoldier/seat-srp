<?PHP 

return [
	'srp' => [
		'name' => 'Ship Replacement Program',
        'label' => 'srp::srp.label-srp',
		'icon' => 'fas fa-rocket',
		'route_segment' => 'srp',
		'permission' => 'srp.request',
		'entries' => [
			[
				'name' => 'Request',
                'label' => 'srp::srp.label-request',
				'icon' => 'fas fa-medkit',
				'route' => 'srp.request',
				'permission' => 'srp.request',
			],
			[
				'name' => 'Approval',
                'label' => 'srp::srp.label-review',
				'icon' => 'fas fa-gavel',
				'route' => 'srpadmin.list',
				'permission' => 'srp.settle',
			],
            [
                'name' => 'Metrics',
                'label' => 'srp::srp.metrics',
                'icon' => 'fas fa-chart-bar',
                'route' => 'srp.metrics',
                'permission' => 'srp.settle',
            ],
            [
                'name' => 'Export',
                'label' => '导出数据',
                'icon' => 'fas fa-chart-bar',
                'route' => 'srp.export-page',
                'permission' => 'srp.settle',
            ],
		],
	],
];
