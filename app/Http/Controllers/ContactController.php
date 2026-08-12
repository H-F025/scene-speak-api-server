<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactReceived;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create([
        ...$request->validated(),
        'user_id' => $request->user()->id,
        ]);

    try {
        Mail::to(config('mail.contact_to'))->send(new ContactReceived($contact));
        } catch (\Throwable $e) {
        report($e);
        }

    return response()->json(['message' => 'お問い合わせを受け付けました。'], 201);
    }
}