<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Basicinfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $logo = $request->file('logo');
            $safeName = Str::slug(pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('admin/site', $safeName, 'r2');
            $webinfo->logo = $r2BaseUrl . '/' . $path;
        }
        
        if($request->fav_icon){  
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $logof = $request->file('fav_icon');
            $safeName = Str::slug(pathinfo($logof->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $logof->getClientOriginalExtension();
            $path = $logof->storeAs('admin/site', $safeName, 'r2');
            $webinfo->fav_icon = $r2BaseUrl . '/' . $path;
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
        
        // Save Facebook Pixel ID and auto-generate script for Blade views
        $pixelId = trim($request->facebook_pixel_id ?? '');
        $webinfo->facebook_pixel_id = $pixelId;
        if($pixelId){
            $webinfo->facebook_pixel = "<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$pixelId}');
fbq('track', 'PageView');
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"/></noscript>
<!-- End Facebook Pixel Code -->";
        }else{
            $webinfo->facebook_pixel='';
        }
        
        // Save GTM Container ID and auto-generate script for Blade views
        $gtmId = trim($request->gtm_id ?? '');
        $webinfo->gtm_id = $gtmId;
        if($gtmId){
            $webinfo->google_analytics = "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$gtmId}');</script>
<!-- End Google Tag Manager -->";
        }else{
            $webinfo->google_analytics='';
        }

        // Save Google Analytics 4 Measurement ID (for standalone gtag.js)
        $gaId = trim($request->google_analytics_id ?? '');
        $webinfo->google_analytics_id = $gaId ?: null;

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
        $webinfo->save();
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
        $webinfo->save();
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
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $logo = $request->file('meta_image');
            $safeName = Str::slug(pathinfo($logo->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('admin/site/seo', $safeName, 'r2');
            $webinfo->meta_image = $r2BaseUrl . '/' . $path;
        }


        $webinfo->save();
        return redirect()->back()->with('message','Shipping info updated successfully');
    }

    /**
     * Update app version management fields (force-update mechanism).
     */
    public function updateAppVersion(Request $request, $id)
    {
        $request->validate([
            'android_app_version_code' => ['required', 'integer', 'min:1'],
            'android_play_store_url'   => ['nullable', 'url'],
        ]);

        $webinfo = Basicinfo::where('id', $id)->first();
        $webinfo->android_app_version_code = $request->android_app_version_code;
        $webinfo->android_play_store_url   = $request->android_play_store_url;
        $webinfo->save();

        return redirect()->back()->with('message', 'App version settings updated successfully');
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
