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

        // --- PERBAIKAN LOGIKA DIMULAI DI SINI ---

        // 1. Kelompokkan semua user di level ini berdasarkan nomor sequence-nya
        const groupedBySequence = lvl.users.reduce((acc, user) => {
            const seq = user.sequence; // Ambil nomor sequence
            if (!acc[seq]) {
                acc[seq] = []; // Jika sequence ini baru, buat array kosong
            }
            acc[seq].push(user); // Masukkan user ke grup sequence-nya
            return acc;
        }, {});

        // 2. Ubah objek yang sudah dikelompokkan menjadi format 'pairs' yang kita butuhkan
        const pairs = Object.values(groupedBySequence).map(usersInSequence => {
            const requesters = usersInSequence
                .filter(u => u.role === 'requester')
                .map(u => u.user_id);
            const approvers = usersInSequence
                .filter(u => u.role === 'approver')
                .map(u => u.user_id);

            return { requesters: requesters, approvers: approvers };
        });

        // --- PERBAIKAN LOGIKA SELESAI ---

        // 3. Render HTML dengan data 'pairs' yang sudah terstruktur dengan benar
        container.append(levelHtml(i, lvl.level, lvl.amount_limit, pairs, lvl.id));
    });

    initSelect2(container);

    // Set selected values
    ruleData.levels.forEach((lvl, i) => {
        // Lakukan pengelompokan yang sama lagi untuk mencocokkan data
        const groupedBySequence = lvl.users.reduce((acc, user) => {
            const seq = user.sequence;
            if (!acc[seq]) { acc[seq] = []; }
            acc[seq].push(user);
            return acc;
        }, {});

        // Loop melalui setiap grup sequence
        Object.values(groupedBySequence).forEach((usersInSequence, pairIndex) => {
            // Dapatkan requester & approver untuk sequence spesifik ini
            const reqs = usersInSequence.filter(u => u.role === 'requester').map(u => u.user_id);
            const apps = usersInSequence.filter(u => u.role === 'approver').map(u => u.user_id);
            
            // Temukan elemen HTML '.user-pair' yang sesuai dengan index-nya
            const pairContainer = $(`#pair-container-${i} .user-pair[data-pair="${pairIndex}"]`);

            // Set nilai yang sudah dipilih untuk pair spesifik ini
            setPreselectedUsers(pairContainer, '.requester', reqs);
            setPreselectedUsers(pairContainer, '.approver', apps);
        });
    });

    // Tambah level (logika ini sudah benar)
    $('#add-level').click(function() {
        const newIndex = $('#levels-container .level-card').length;
        container.append(levelHtml(newIndex));
        initSelect2(container);
    });

    // Hapus level (logika ini sudah benar)
    $(document).on('click', '.remove-level', function() {
        $(this).closest('.level-card').remove();
        $('#levels-container .level-card').each(function(i, el) {
            $(el).attr('data-idx', i);
            $(el).find('[name]').each(function() {
                let name = $(this).attr('name');
                name = name.replace(/levels\[\d+\]/, `levels[${i}]`);
                $(this).attr('name', name);
            });
            $(el).find('input[name*="[level]"]').val(i + 1);
        });
    });

    // Tambah pair (logika ini sudah benar)
    $(document).on('click', '.add-pair', function () {
        const levelIdx = $(this).data('level');
        const pairContainer = $(`#pair-container-${levelIdx}`);
        const pairIdx = pairContainer.find('.user-pair').length;
        pairContainer.append(userPairRowHtml(levelIdx, pairIdx));
        initSelect2(pairContainer);
    });

    // Hapus pair (logika ini sudah benar)
    $(document).on('click', '.remove-pair', function () {
        $(this).closest('.user-pair').remove();
    });
});
</script>
@endpush
