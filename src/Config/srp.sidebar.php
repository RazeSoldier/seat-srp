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
				'icon' => 'fa-gavel',
				'route' => 'srpadmin.list',
				'permission' => 'srp.settle',
			],
            [
                'name' => 'Metrics',
                'icon' => 'fa-bar-chart',
                'route' => 'srp.metrics',
                'permission' => 'srp.settle',
            ],
		],
	],
];
