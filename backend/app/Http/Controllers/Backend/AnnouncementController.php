<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DataTables;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('backend.content.announcements.index');
    }

    public function store(Request $request)
    {
        $announcement = new Announcement();
        $announcement->title = $request->title;
        $announcement->description = $request->description;
        $announcement->status = $request->status ?? 'Active';
        $announcement->published_at = now();

        $img = $request->file('image');
        if ($img) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url', ''), '/');
            if ($r2BaseUrl) {
                $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
                $path = $img->storeAs('announcements', $safeName, 'r2');
                $announcement->image = $r2BaseUrl . '/' . $path;
            } else {
                $imgName = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('images/announcements'), $imgName);
                $announcement->image = 'images/announcements/' . $imgName;
            }
        }

        $announcement->save();
        return response()->json($announcement, 200);
    }

    public function announcementdata()
    {
        $announcements = Announcement::orderBy('id', 'desc')->get();
        return Datatables::of($announcements)
            ->addColumn('image_preview', function ($row) {
                if ($row->image) {
                    $src = Str::startsWith($row->image, 'http')
                        ? $row->image
                        : asset(preg_replace('#^public/#', '', $row->image));
                    return '<img src="' . $src . '" style="max-height:40px;border-radius:6px;" />';
                }
                return '<span class="text-muted">—</span>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="#" type="button" id="editAnnouncementBtn" data-id="' . $row->id . '" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteAnnouncementBtn" data-id="' . $row->id . '" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>';
            })
            ->rawColumns(['image_preview', 'action'])
            ->make(true);
    }

    public function edit($id)
    {
        $announcement = Announcement::findOrFail($id);
        return response()->json($announcement, 200);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->title = $request->title;
        $announcement->description = $request->description;

        $img = $request->file('image');
        if ($img) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url', ''), '/');
            if ($r2BaseUrl) {
                $safeName = Str::slug(pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME))
                    . '_' . Str::random(8) . '.' . $img->getClientOriginalExtension();
                $path = $img->storeAs('announcements', $safeName, 'r2');
                $announcement->image = $r2BaseUrl . '/' . $path;
            } else {
                $imgName = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('images/announcements'), $imgName);
                $announcement->image = 'images/announcements/' . $imgName;
            }
        }

        $announcement->update();
        return response()->json($announcement, 200);
    }

    public function destroy($id)
    {
        $announcement = Announcement::where('id', $id)->first();
        $announcement->delete();
        return response()->json('success', 200);
    }

    public function updatestatus(Request $request)
    {
        $announcement = Announcement::where('id', $request->announcement_id)->first();
        $announcement->status = $request->status;
        $announcement->update();
        return response()->json($announcement, 200);
    }
}
