<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = $request->user()->certificates()->with('course')->latest('issued_at')->get();

        return view('certificates.index', compact('certificates'));
    }

    /** صفحة عامة للتحقق وطباعة الشهادة */
    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'course.instructor']);

        return view('certificates.show', compact('certificate'));
    }
}
