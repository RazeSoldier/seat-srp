<?PHP 

return [
	'srp' => [
		'name' => 'Ship Replacement Program',
        'label' => 'srp::srp.label-srp',
		'icon' => 'fa-rocket',
		'route_segment' => 'srp',
		'permission' => 'srp.request',
		'entries' => [
			[
				'name' => 'Request',
                'label' => 'srp::srp.label-request',
				'icon' => 'fa-medkit',
				'route' => 'srp.request',
				'permission' => 'srp.request',
			],
			[
				'name' => 'Approval',
                'label' => 'srp::srp.label-review',
				'icon' => 'fa-gavel',
				'route' => 'srpadmin.list',
				'permission' => 'srp.admin-readonly',
			],
            [
                'name' => 'Metrics',
                'label' => 'srp::srp.metrics',
                'icon' => 'fa-bar-chart',
                'route' => 'srp.metrics',
                'permission' => 'srp.settle',
            ],
            [
                'name' => 'Export',
                'label' => '导出数据',
                'icon' => 'fa-bar-chart',
                'route' => 'srp.export-page',
                'permission' => 'srp.settle',
            ],
		],
	],
];
