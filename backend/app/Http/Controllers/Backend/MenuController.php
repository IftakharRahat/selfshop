<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;

class MenuController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.menu.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $menu =new Menu();
        $menu->menu_name =$request->menu_name;
        $menu_banner = $request->file('menu_banner');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $safeName = Str::slug(pathinfo($menu_banner->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $menu_banner->getClientOriginalExtension();
        $path = $menu_banner->storeAs('admin/menus', $safeName, 'r2');
        $menu->menu_banner = $r2BaseUrl . '/' . $path;
        $menu->save();
        return response()->json($menu, 200);
    }

    public function menudata()
    {
        $menus = Menu::all();
        return Datatables::of($menus)
            ->addColumn('action', function ($menus) {
                return '<a href="#" type="button" id="editMenuBtn" data-id="' . $menus->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainMenu" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteMenuBtn" data-id="' . $menus->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menu = Menu::findOrfail($id);
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::findOrfail($id);
        $menu->menu_name =$request->menu_name;
        if($request->menu_banner){
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $menu_banner = $request->file('menu_banner');
            $safeName = Str::slug(pathinfo($menu_banner->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $menu_banner->getClientOriginalExtension();
            $path = $menu_banner->storeAs('admin/menus', $safeName, 'r2');
            $menu->menu_banner = $r2BaseUrl . '/' . $path;
        }
        $menu->save();
        return response()->json($menu, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $menu = Menu::findOrfail($id);
        $menu->delete();
        return response()->json('success', 200);
    }

    public function statusupdate(Request $request)
    {
        $menu = Menu::where('id',$request->menu_id)->first();
        $menu->status=$request->status;
        $menu->update();
        return response()->json($menu, 200);
    }
}