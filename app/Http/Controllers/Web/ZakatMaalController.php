<?php

namespace App\Http\Controllers\Web;

use App\Exports\ZakatMaalExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ZakatMaal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ZakatMaalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $q = $request->get('q', false);

        $zakatMaal = $user->hasRole('Amil Zakat') ? $user->zakatMaal()->when($q, function ($query) use ($q) {
            $query->where('nama_muzaki', 'LIKE', "%$q%");
        })->get() : ZakatMaal::when($q, function ($query) use ($q) {
            $query->where('nama_muzaki', 'LIKE', "%$q%");
        })->get();

        return view('zakat_maal')->with('zakatMaal', $zakatMaal);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::role('Amil Zakat')->get();

        return view('zakat_maal.create')->with('users', $users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $total = (int) $request->input('nominal_zakat_maal') + (int) $request->input('nominal_infaq_shedekah');

        $request->merge([
            'total' => $total,
            'user_id' => $user->hasRole('Super Admin') ? $request->input('user_id') : Auth::id(),
            'tanggal' => date('Y-m-d'),
        ]);

        $request->validate([
            'nama_muzaki' => ['required'],
            'alamat' => ['nullable'],
            'jenis_harta' => ['nullable'],
            'nominal_zakat_maal' => ['nullable', 'numeric'],
            'nominal_infaq_shedekah' => ['nullable', 'numeric'],
            'keterangan' => ['nullable'],
            'user_id' => ['required'],
        ]);

        ZakatMaal::create($request->all());

        return redirect()->route('zakat_maal')->with('success', 'Berhasil menambahkan zakat maal / infaq / shedekah.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ZakatMaal $zakatMaal)
    {
        return view('zakat_maal.edit')->with('zakatMaal', $zakatMaal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ZakatMaal $zakatMaal)
    {
        $request->validate([
            'nama_muzaki' => ['required'],
            'alamat' => ['nullable'],
            'jenis_harta' => ['nullable'],
            'nominal_zakat_maal' => ['nullable', 'numeric'],
            'nominal_infaq_shedekah' => ['nullable', 'numeric'],
            'keterangan' => ['nullable'],
        ]);

        $total = (int) $request->input('nominal_zakat_maal') + (int) $request->input('nominal_infaq_shedekah');

        $request->merge([
            'total' => $total,
        ]);

        $zakatMaal->update($request->all());

        return redirect()->route('zakat_maal')->with('success', 'Berhasil memperbarui zakat maal / infaq / shedekah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ZakatMaal $zakatMaal)
    {
        $zakatMaal->delete();

        return redirect()->route('zakat_maal')->with('success', 'Berhasil menghapus zakat maal / infaq / shedekah.');
    }

    public function export(Request $request)
    {
        return Excel::download(new ZakatMaalExport, 'Zakat Maal.xlsx');
    }
}
