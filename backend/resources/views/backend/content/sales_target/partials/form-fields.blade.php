<div class="mb-3">
    <label class="form-label">Title <span class="text-danger">*</span></label>
    <input
        type="text"
        class="form-control"
        name="title"
        id="{{ $mode }}_title"
        value="{{ old('title') }}"
        required
    >
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" id="{{ $mode }}_description" rows="2">{{ old('description') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Target Type <span class="text-danger">*</span></label>
        <select class="form-select" name="target_type" id="{{ $mode }}_target_type" required>
            <option value="amount">Amount</option>
            <option value="quantity">Quantity</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Target Value <span class="text-danger">*</span></label>
        <input
            type="number"
            step="0.01"
            min="1"
            class="form-control"
            name="target_value"
            id="{{ $mode }}_target_value"
            value="{{ old('target_value') }}"
            required
        >
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Count Order Scope <span class="text-danger">*</span></label>
        <select class="form-select" name="order_scope" id="{{ $mode }}_order_scope" required>
            <option value="non_canceled">Non Canceled</option>
            <option value="delivered">Delivered Only</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Priority</label>
        <input
            type="number"
            min="0"
            class="form-control"
            name="priority"
            id="{{ $mode }}_priority"
            value="{{ old('priority', 0) }}"
        >
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Reward Type <span class="text-danger">*</span></label>
        <select class="form-select" name="reward_type" id="{{ $mode }}_reward_type" required>
            <option value="bonus">Bonus</option>
            <option value="gift">Gift</option>
            <option value="reward">Reward</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Reward Value (TK)</label>
        <input
            type="number"
            step="0.01"
            min="0"
            class="form-control"
            name="reward_value"
            id="{{ $mode }}_reward_value"
            value="{{ old('reward_value') }}"
        >
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Reward Note</label>
    <input
        type="text"
        class="form-control"
        name="reward_note"
        id="{{ $mode }}_reward_note"
        value="{{ old('reward_note') }}"
    >
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" class="form-control" name="start_date" id="{{ $mode }}_start_date" value="{{ old('start_date') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">End Date</label>
        <input type="date" class="form-control" name="end_date" id="{{ $mode }}_end_date" value="{{ old('end_date') }}">
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Status <span class="text-danger">*</span></label>
    <select class="form-select" name="status" id="{{ $mode }}_status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
    </select>
</div>
