<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Addbanner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AddbannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $addbanners = Addbanner::all();
        return view('backend.content.addbanner.index', ['addbanners' => $addbanners]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function statusupdate(Request $request, $id)
    {
        $addbanner = Addbanner::findOrfail($id);
        $addbanner->status = $request->status;
        $addbanner->update();
        return redirect()->back()->with('message', 'Add banner status updated');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $addbanner = new Addbanner();
        $addbanner->title = $request->title;
        $addbanner->text = $request->text;

        if ($request->icon) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $icon = $request->file('icon');
            $safeName = Str::slug(pathinfo($icon->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $icon->getClientOriginalExtension();
            $path = $icon->storeAs('admin/banners', $safeName, 'r2');
            $addbanner->icon = $r2BaseUrl . '/' . $path;
        }

        if ($request->bg_img) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $add_image = $request->file('bg_img');
            $safeName = Str::slug(pathinfo($add_image->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $add_image->getClientOriginalExtension();
            $path = $add_image->storeAs('admin/banners', $safeName, 'r2');
            $addbanner->bg_img = $r2BaseUrl . '/' . $path;
        }
        $addbanner->save();
        return redirect()->route('admin.addbanners.index')->with('message', 'Add Banner Created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Addbanner  $addbanner
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.content.addbanner.create');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Addbanner  $addbanner
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $addbanner = Addbanner::findOrfail($id);
        return view('backend.content.addbanner.edit', ['addbanner' => $addbanner]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Addbanner  $addbanner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $addbanner = Addbanner::findOrfail($id);
        $addbanner->title = $request->title;
        $addbanner->text = $request->text;

        if ($request->icon) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $icon = $request->file('icon');
            $safeName = Str::slug(pathinfo($icon->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $icon->getClientOriginalExtension();
            $path = $icon->storeAs('admin/banners', $safeName, 'r2');
            $addbanner->icon = $r2BaseUrl . '/' . $path;
        }

        if ($request->bg_img) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $add_image = $request->file('bg_img');
            $safeName = Str::slug(pathinfo($add_image->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $add_image->getClientOriginalExtension();
            $path = $add_image->storeAs('admin/banners', $safeName, 'r2');
            $addbanner->bg_img = $r2BaseUrl . '/' . $path;
        }
        $addbanner->update();
        return redirect()->route('admin.addbanners.index')->with('message', 'Add Banner Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Addbanner  $addbanner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Addbanner $addbanner)
    {
        //
    }
}
