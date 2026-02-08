<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Basicinfo;
use Illuminate\Http\Request;

class BasicinfoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $webinfo =Basicinfo::first();
        return view('backend.content.basicinfo.index',['webinfo'=>$webinfo]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Basicinfo  $basicinfo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $webinfo =Basicinfo::where('id',$id)->first();
        $webinfo->email=$request->email;
        $webinfo->bonus_percent=$request->bonus_percent;
        $webinfo->wp_number=$request-> wp_number;
        $webinfo->phone_one=$request-> phone_one;
        $webinfo->phone_two=$request-> phone_two;
        $webinfo->address=$request-> address;
        if($request->logo){
            if($webinfo->logo =='public/webview/assets/images/logo.png'){
            }else{
                unlink($webinfo->logo);
            }
            $logo = $request->file('logo');
            $name = time() . "_" . $logo->getClientOriginalName();
            $uploadPath = ('public/images/categorybanner/');
            $logo->move($uploadPath, $name);
            $logoImgUrl = $uploadPath . $name;
            $webinfo->logo = $logoImgUrl;
        }
        
        if($request->fav_icon){  
            $logof = $request->file('fav_icon');
            $namef = time() . "_" . $logof->getClientOriginalName();
            $uploadPathf = ('public/images/fav/');
            $logof->move($uploadPathf, $namef);
            $logoImgUrlf = $uploadPathf . $namef;
            $webinfo->fav_icon = $logoImgUrlf;
        }
        $webinfo->save();
        return redirect()->back()->with('message','Info updated successfully');
    }

    public function pixelanalytics(Request $request, $id)
    {
        $webinfo =Basicinfo::where('id',$id)->first();
        
        if($request->invoice_footer){
            $webinfo->invoice_footer=$request->invoice_footer;
        }else{
            $webinfo->invoice_footer='';
        }
        
        if($request->facebook_pixel){
            $webinfo->facebook_pixel=$request->facebook_pixel;
        }else{
            $webinfo->facebook_pixel='';
        }
        
        if($request->google_analytics){
            $webinfo->google_analytics=$request->google_analytics;
        }else{
            $webinfo->google_analytics='';
        }
        if($request->marquee_text){
            $webinfo->marquee_text=$request->marquee_text;
        }else{
            $webinfo->marquee_text='';
        }
        if($request->chat_box){
            $webinfo->chat_box=$request->chat_box;
        }else{
            $webinfo->chat_box='';
        }
        $webinfo->update();
        return redirect()->back()->with('message','Pixel & Analytics updated successfully');
    }

    public function sociallink(Request $request, $id)
    {
        $webinfo =Basicinfo::where('id',$id)->first();
        if(isset($request->wp_link)){
            $webinfo->wp_link=$request->wp_link;
        }else{
            $webinfo->wp_link=null;
        }
        if(isset($request->messanger_link)){
            $webinfo->messanger_link=$request->messanger_link;
        }else{
            $webinfo->messanger_link=null;
        }
        if(isset($request->facebook)){
            $webinfo->facebook=$request->facebook;
        }else{
            $webinfo->facebook=null;
        }
        if(isset($request->twitter)){
            $webinfo->twitter=$request->twitter;
        }else{
            $webinfo->twitter=null;
        }
        if(isset($request->google)){
            $webinfo->google=$request->google;
        }else{
            $webinfo->google=null;
        }
        if(isset($request->rss)){
            $webinfo->rss=$request->rss;
        }else{
            $webinfo->rss=null;
        }
        if(isset($request->pinterest)){
            $webinfo->pinterest=$request->pinterest;
        }else{
            $webinfo->pinterest=null;
        }
        if(isset($request->linkedin)){
            $webinfo->linkedin=$request->linkedin;
        }else{
            $webinfo->linkedin=null;
        }
        if(isset($request->youtube)){
            $webinfo->youtube=$request->youtube;
        }else{
            $webinfo->youtube=null;
        }
        $webinfo->update();
        return redirect()->back()->with('message','Social Links updated successfully');
    }

     public function shippinginfo(Request $request, $id)
    {
        $webinfo =Basicinfo::where('id',$id)->first();
        if(isset($request->inside_dhaka_charge)){
            $webinfo->inside_dhaka_charge=$request->inside_dhaka_charge;
        }else{
            $webinfo->inside_dhaka_charge=null;
        }
        if(isset($request->outside_dhaka_charge)){
            $webinfo->outside_dhaka_charge=$request->outside_dhaka_charge;
        }else{
            $webinfo->outside_dhaka_charge=null;
        }

        if(isset($request->near_dhaka_charge)){
            $webinfo->near_dhaka_charge=$request->near_dhaka_charge;
        }else{
            $webinfo->near_dhaka_charge=null;
        }

        if(isset($request->insie_dhaka)){
            $webinfo->insie_dhaka=$request->insie_dhaka;
        }else{
            $webinfo->insie_dhaka=null;
        }
        if(isset($request->outside_dhaka)){
            $webinfo->outside_dhaka=$request->outside_dhaka;
        }else{
            $webinfo->outside_dhaka=null;
        }
        if(isset($request->cash_on_delivery)){
            $webinfo->cash_on_delivery=$request->cash_on_delivery;
        }else{
            $webinfo->cash_on_delivery=null;
        }
        if(isset($request->refund_rule)){
            $webinfo->refund_rule=$request->refund_rule;
        }else{
            $webinfo->refund_rule=null;
        }
        if(isset($request->contact)){
            $webinfo->contact=$request->contact;
        }else{
            $webinfo->contact=null;
        }

        if(isset($request->b_one)){
            $webinfo->b_one=$request->b_one;
        }else{
            $webinfo->b_one=null;
        }

        if(isset($request->b_two)){
            $webinfo->b_two=$request->b_two;
        }else{
            $webinfo->b_two=null;
        }

        if(isset($request->b_three)){
            $webinfo->b_three=$request->b_three;
        }else{
            $webinfo->b_three=null;
        }

         if(isset($request->title)){
            $webinfo->title=$request->title;
        }else{
            $webinfo->title=null;
        }

        if(isset($request->meta_description)){
            $webinfo->meta_description=$request->meta_description;
        }else{
            $webinfo->meta_description=null;
        }

        if(isset($request->meta_keyword)){
            $webinfo->meta_keyword=$request->meta_keyword;
        }else{
            $webinfo->meta_keyword=null;
        }

        if($request->meta_image){
            $logo = $request->file('meta_image');
            $name = time() . "_" . $logo->getClientOriginalName();
            $uploadPath = ('public/images/meta_image/');
            $logo->move($uploadPath, $name);
            $logoImgUrl = $uploadPath . $name;
            $webinfo->meta_image = $logoImgUrl;
        }


        $webinfo->update();
        return redirect()->back()->with('message','Shipping info updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Basicinfo  $basicinfo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Basicinfo $basicinfo)
    {
        //
    }
}
