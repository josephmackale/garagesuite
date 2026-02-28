<div data-module="insurance-claim">
  @if(!data_get($gates, 'can_view_claim.ok'))
    <div class="alert alert-warning">
      {{ data_get($gates, 'can_view_claim.reason') }}
    </div>
  @else
    <div class="card">
      <div class="card-header">
        <strong>Claim</strong>
        <span class="text-muted ms-2">
          Insurer: {{ data_get($details, 'insurer_name') }}
        </span>
      </div>

      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Claim Number</label>
          <input type="text" class="form-control"
                 name="claim_number"
                 value="{{ old('claim_number', data_get($details, 'claim_number')) }}"
                 @disabled(!data_get($gates, 'can_edit_claim.ok'))>
          @if(!data_get($gates,'can_edit_claim.ok'))
            <small class="text-muted">{{ data_get($gates,'can_edit_claim.reason') }}</small>
          @endif
        </div>

        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="3"
            @disabled(!data_get($gates, 'can_edit_claim.ok'))>{{ old('notes', data_get($details,'notes')) }}</textarea>
        </div>

        <button class="btn btn-primary"
                data-action="save-claim"
                @disabled(!data_get($gates,'can_edit_claim.ok'))>
          Save
        </button>

        <button class="btn btn-success ms-2"
                data-action="submit-claim"
                @disabled(!data_get($gates,'can_submit_claim.ok'))
                title="{{ data_get($gates,'can_submit_claim.ok') ? '' : data_get($gates,'can_submit_claim.reason') }}">
          Submit Claim
        </button>
      </div>
    </div>
  @endif
</div>