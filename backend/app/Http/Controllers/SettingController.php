<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Courier;
use Illuminate\Http\Request;
use DataTables;


class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        return view('auth.setting');
    }

 
 











}