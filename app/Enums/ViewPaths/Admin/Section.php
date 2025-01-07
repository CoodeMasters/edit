<?php

namespace App\Enums\ViewPaths\Admin;

enum Section
{
    const LIST = [
        URI => 'list',
        VIEW => 'admin-views.section.view'
    ];

    const ADD = [
        URI => 'add',
        VIEW => ''
    ];

    const DELETE = [
        URI => 'delete',
        VIEW => ''
    ];

    const STATUS = [
        URI => 'status',
        VIEW => ''
    ];

    const UPDATE = [
        URI => 'update',
        VIEW => 'admin-views.section.edit',
        ROUTE => 'admin.section.list'
    ];
}
