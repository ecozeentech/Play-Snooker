<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Services\PlatformBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request, PlatformBranding $branding): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to($branding->contactEmail())->send(new ContactMessageMail(
            $data['name'],
            $data['email'],
            $data['subject'],
            $data['message'],
        ));

        return back()->with('success', "Thanks {$data['name']}! Your message has been sent — we'll get back to you soon.");
    }
}
