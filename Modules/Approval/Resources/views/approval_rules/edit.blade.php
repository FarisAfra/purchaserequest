@extends('layouts.app')
@section('title','Edit Approval Rule')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('approval_rules.index') }}">Approval Rules</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    @include('utils.alerts')
    <form method="POST" action="{{ route('approval_rules.update', $rule->id) }}" id="rule-form">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">
                <div class="form-row mb-3">
                    <div class="col-md-4">
                        <label>Approval Type</label>
                        <select name="approval_types_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" {{ $rule->approval_types_id == $t->id ? 'selected' : '' }}>
                                    {{ $t->approval_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Rule Name</label>
                        <input name="rule_name" class="form-control" value="{{ old('rule_name', $rule->rule_name) }}" />
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}> Active
                    </div>
                </div>

                <div>
                    <h5>Levels</h5>
                    <div id="levels-container"></div>
                    <button type="button" id="add-level" class="btn btn-sm btn-primary mt-2">+ Tambah Level Baru</button>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-success">Update</button>
                <a href="{{ route('approval_rules.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('page_scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let levelIndex = 0;
const ruleData = @json($rule);

function userPairRowHtml(levelIdx, pairIdx, requesters = [], approvers = []) {
    return `
    <div class="row align-items-end mb-2 user-pair" data-pair="${pairIdx}">
        <div class="col-md-5">
            <label>Requester</label>
            <select name="levels[${levelIdx}][pairs][${pairIdx}][requester][]" 
                class="form-control select2-user requester" multiple required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label>Approver</label>
            <select name="levels[${levelIdx}][pairs][${pairIdx}][approver][]" 
                class="form-control select2-user approver" multiple required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 text-center">
            <button type="button" class="btn btn-danger btn-sm remove-pair">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>`;
}

function levelHtml(idx, levelNumber = '', amountLimit = '', pairs = [], levelId = null) {
    let pairHtml = '';
    
    // Logika ini untuk memastikan level baru juga punya satu baris pair
    if (pairs.length > 0) {
        pairs.forEach((pair, i) => {
            pairHtml += userPairRowHtml(idx, i, pair.requesters, pair.approvers);
        });
    } else {
        pairHtml = userPairRowHtml(idx, 0);
    }

    return `
    <div class="level-card border rounded-3 p-3 mb-3" data-idx="${idx}">
        <input type="hidden" name="levels[${idx}][id]" value="${levelId ?? ''}">
        <div class="row mb-3">
            <div class="col-md-2">
                <label>Level</label>
                {{-- PERBAIKAN ADA DI BARIS DI BAWAH INI --}}
                <input type="number" name="levels[${idx}][level]" class="form-control" value="${levelNumber || (idx + 1)}" readonly>
            </div>
            <div class="col-md-3">
                <label>Amount Limit</label>
                <input type="number" step="0.01" name="levels[${idx}][amount_limit]" class="form-control" value="${amountLimit}" required>
            </div>
            <div class="col-md-3 text-end">
                <label>&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-sm remove-level">
                    <i class="bi bi-trash"></i> Hapus Level
                </button>
            </div>
        </div>

        <div class="user-pair-container" id="pair-container-${idx}">
            ${pairHtml}
        </div>
        <button type="button" class="btn btn-sm btn-primary mt-2 add-pair" data-level="${idx}">
            + Tambah Requester & Approver
        </button>
    </div>`;
}

function initSelect2(container) {
    container.find('.select2-user').select2({
        placeholder: "Cari dan pilih user...",
        allowClear: true
    });
}

function setPreselectedUsers(container, selector, selectedIds) {
    container.find(selector).each(function () {
        const select = $(this);
        const values = selectedIds.map(id => id.toString());
        select.val(values).trigger('change');
    });
}

$(function () {
    const container = $('#levels-container');

    // Tampilkan data level & user yang sudah ada
    ruleData.levels.forEach((lvl, i) => {
        const pairs = [];
        
        // --- PERBAIKAN DI SINI ---
        // Hapus .pivot. Akses properti langsung dari 'u'
        const reqs = lvl.users.filter(u => u.role === 'requester').map(u => u.user_id);
        const apps = lvl.users.filter(u => u.role === 'approver').map(u => u.user_id);
        // --- BATAS PERBAIKAN ---

        pairs.push({ requesters: reqs, approvers: apps });

        container.append(levelHtml(i, lvl.level, lvl.amount_limit, pairs, lvl.id)); 

    });

    initSelect2(container);

    // Set selected values
    ruleData.levels.forEach((lvl, i) => {
        
        // --- PERBAIKAN DI SINI JUGA ---
        // Hapus .pivot. Akses properti langsung dari 'u'
        const reqs = lvl.users.filter(u => u.role === 'requester').map(u => u.user_id);
        const apps = lvl.users.filter(u => u.role === 'approver').map(u => u.user_id);
        // --- BATAS PERBAIKAN ---

        const pairContainer = $(`#pair-container-${i}`);

        pairContainer.find('.user-pair').each(function () {
            setPreselectedUsers($(this), '.requester', reqs);
            setPreselectedUsers($(this), '.approver', apps);
        });
    });

    // Tambah level
    $('#add-level').click(function() {
    // 1. Hitung index baru berdasarkan jumlah elemen yang ada SAAT INI.
    const newIndex = $('#levels-container .level-card').length;

    // 2. Gunakan index baru tersebut untuk membuat HTML.
    container.append(levelHtml(newIndex));
    initSelect2(container);
});

    // Hapus level
    $(document).on('click', '.remove-level', function() {
    $(this).closest('.level-card').remove();
    
    // Setelah menghapus, kita perlu mengindeks ulang semua level yang tersisa
    $('#levels-container .level-card').each(function(i, el) {
        // 'i' adalah index baru yang benar (0, 1, 2, ...)
        
        // Update data-idx atribut
        $(el).attr('data-idx', i);

        // Update semua atribut 'name' di dalamnya agar berurutan
        $(el).find('[name]').each(function() {
            let name = $(this).attr('name');
            // Ganti 'levels[angka_lama]' menjadi 'levels[i_baru]'
            name = name.replace(/levels\[\d+\]/, `levels[${i}]`);
            $(this).attr('name', name);
        });

        // Update nilai visual di input 'Level'
        $(el).find('input[name*="[level]"]').val(i + 1);
    });
    
    // Tidak perlu lagi mengelola levelIndex secara manual!
});

    // Tambah pair
    $(document).on('click', '.add-pair', function () {
        const levelIdx = $(this).data('level');
        const pairContainer = $(`#pair-container-${levelIdx}`);
        const pairIdx = pairContainer.find('.user-pair').length;
        pairContainer.append(userPairRowHtml(levelIdx, pairIdx));
        initSelect2(pairContainer);
    });

    // Hapus pair
    $(document).on('click', '.remove-pair', function () {
        $(this).closest('.user-pair').remove();
    });
});
</script>
@endpush
