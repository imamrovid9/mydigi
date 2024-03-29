@extends('admin.layouts.master')

@section('title', 'Edit User Group')

@section('head_style')
  <!-- custom-checkbox -->
  <link rel="stylesheet" type="text/css" href="{{ asset('public/dist/css/custom-checkbox.css') }}">
@endsection

@section('page_content')
  <div class="row">
      <div class="col-md-3 settings_bar_gap">
          @include('admin.common.settings_bar')
      </div>
      <div class="col-md-9">
          <!-- Horizontal Form -->
          <div class="box box-info">
              <div class="box-header with-border text-center">
                  <h3 class="box-title">Edit User Group</h3>
              </div>

              <!-- form start -->
              <form method="POST" action="{{ url('admin/settings/edit_user_role/'. $result->id) }}"
                    class="form-horizontal" id="group_edit_form">
                  {{ csrf_field() }}

                  <div class="box-body">
                      <div class="form-group">
                          <label class="col-sm-3 control-label">Name</label>
                          <div class="col-sm-6">
                              <input type="text" name="name" class="form-control" value="{{ $result->name }}" placeholder="Name" id="name">
                              @if($errors->has('name'))
                                  <span class="help-block">
                                      <strong class="text-danger">{{ $errors->first('name') }}</strong>
                                  </span>
                              @endif
                              <span id="name-error"></span>
                              <span id="name-ok" class="text-success"></span>
                          </div>
                      </div>

                      <div class="form-group">
                          <label class="col-sm-3 control-label">Display Name</label>
                          <div class="col-sm-6">
                              <input placeholder="Display Name" type="text" class="form-control" name="display_name"
                                     value="{{ $result->display_name }}" id="display_name">
                              @if($errors->has('display_name'))
                                  <span class="help-block">
                                      <strong class="text-danger">{{ $errors->first('display_name') }}</strong>
                                  </span>
                              @endif
                          </div>
                      </div>

                      <div class="form-group">
                          <label class="col-sm-3 control-label">Description</label>
                          <div class="col-sm-6">
                              <textarea placeholder="Description" rows="3" class="form-control" name="description" id="description">{{ $result->description }}</textarea>
                              @if($errors->has('description'))
                                  <span class="help-block">
                                  <strong class="text-danger">{{ $errors->first('description') }}</strong>
                                </span>
                              @endif
                          </div>
                      </div>

                      <div class="form-group">
                        <label class="col-sm-3 control-label" for="exampleFormControlInput1">User Type</label>
                        <div class="col-sm-6">
                          <select class="select2" name="customer_type" id="customer_type">
                              <option value='user' {{ $result->customer_type == 'user' ? 'selected':"" }}>User</option>
                              <option value='merchant' {{ $result->customer_type == 'merchant' ? 'selected':"" }}>Merchant</option>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-sm-3 control-label" for="exampleFormControlInput1">Default</label>
                        <div class="col-sm-6">
                          <select class="select2" name="default" id="default">
                              <option value='No' {{ $result->is_default == 'No' ? 'selected':"" }}>No</option>
                              <option value='Yes' {{ $result->is_default == 'Yes' ? 'selected':"" }}>Yes</option>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                          <label class="col-sm-3 control-label" for="exampleFormControlInput1"></label>
                          <div class="col-sm-5">
                              <div class="table-responsive">
                                  <table class="table table-striped">
                                      <thead>
                                      <tr>
                                          <th>Permissions</th>
                                          <th>Action</th>
                                      </tr>
                                      </thead>
                                      <tbody id="permissions-tbody">
                                        @php $arr=['Transaction','Dispute','Ticket','Settings'] @endphp
                                        @if (isset($permissions))
                                            @foreach ($permissions as $permission)
                                                <input type="hidden" value="{{ $result->user_type }}" name="user_type" id="user_type">
                                                <input type="hidden" value="{{ $result->id }}" name="id" id="id">
                                                @if(in_array($permission->group,$arr))
                                                    <input style="display: none" type="checkbox" name="permission[]"id="permission" value="{{$permission->id}}" checked>
                                                @else
                                                    <tr>
                                                        <td>{{ $permission->group }}</td>
                                                        <td>
                                                            <label class="checkbox-container">
                                                                <input type="checkbox" name="permission[]" id="permission" value="{{ $permission->id }}"
                                                                {{ in_array( $permission->id, $stored_permissions) ? 'checked' : '' }}>
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                      </tbody>
                                  </table>
                                  <div id="error-message"></div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="box-footer">
                      <a class="btn btn-danger" href="{{ url('admin/settings/user_role') }}">Cancel</a>
                      <button type="submit" class="btn btn-primary pull-right">Update</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>
<script type="text/javascript">

    function checkUserRolePermissionsOnUpdate()
    {
        let role_id = '{{ $result->id }}';
        let customer_type = $("#customer_type option:selected").val();
        $.ajax({
            method: "GET",
            url: SITE_URL+"/admin/settings/roles/check-user-permissions",
            dataType: "json",
            data: {
                'role_id': role_id,
                'customer_type': customer_type,
            }
        })
        .done(function(response)
        {
            // console.log(response);
            if (response.status == true)
            {
                let tr = '';
                let stored_permissions = response.stored_permissions;
                $.each(response.permissions, function(key, val)
                {
                    `<input type="hidden" value="${val.user_type}" name="user_type" id="user_type">
                    <input type="hidden" value="${val.id}" name="id" id="id">`
                    let arr = ['Transaction','Dispute','Ticket','Settings'];
                    if (arr.includes(val.group))
                    {
                      `<input style="display: none" type="checkbox" name="permission[]" id="permission" value="${val.id}" checked>`
                    }
                    else
                    {
                      tr +=
                      '<tr>'+
                        `<input type="hidden" value="${val.user_type}" name="user_type" id="user_type">` +
                        '<td>'+ val.group +'</td>'+
                        '<td>'+
                          `<label class="checkbox-container">
                            <input type="checkbox" name="permission[]" value="${val.id}" ${ stored_permissions.includes(val.id) ? 'checked': ''}>
                            <span class="checkmark"></span>
                          </label>`
                        '</td>'+
                      '</tr>';
                    }
                });
                $('#permissions-tbody').html(tr);
            }
        });
    }

    $(window).on('load', function()
    {
      $(".select2").select2({});
      checkUserRolePermissionsOnUpdate();
    });

    // Validate Role Name via Ajax
    $(document).ready(function()
    {
        $("#name").on('input', function(e)
        {
          var name = $('#name').val();
          var user_type = $('#user_type').val();
          var id = $('#id').val();
          $.ajax({
              headers:
              {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              method: "POST",
              url: SITE_URL+"/admin/settings/roles/duplicate-role-check",
              dataType: "json",
              data: {
                  'name': name,
                  'user_type': user_type,
                  'id': id,
              }
          })
          .done(function(response)
          {
              // console.log(response);
              if (response.status == true)
              {
                  emptyName();
                  $('#name-error').show();
                  $('#name-error').addClass('error').html(response.fail).css("font-weight", "bold");
                  $('form').find("button[type='submit']").prop('disabled',true);
              }
              else if (response.status == false)
              {
                  emptyName();
                  $('#name-error').html('');
                  $('form').find("button[type='submit']").prop('disabled',false);
              }

              function emptyName() {
                  if( name.length === 0 )
                  {
                      $('#name-error').html('');
                  }
              }
          });
        });
    });

    //Check User Role Permissions
    $(document).on('change', "#customer_type", function(e)
    {
      checkUserRolePermissionsOnUpdate();
    });

    //On Submit
    jQuery.validator.addMethod("letters_with_spaces", function (value, element) {
        return this.optional(element) || /^[A-Za-z ]+$/i.test(value); //only letters
    }, "Please enter letters only!");

    $.validator.setDefaults({
        highlight: function (element) {
            $(element).parent('div').addClass('has-error');
        },
        unhighlight: function (element) {
            $(element).parent('div').removeClass('has-error');
        },
        errorPlacement: function (error, element) {
            if (element.prop('type') === 'checkbox') {
                $('#error-message').html(error);
            } else {
                error.insertAfter(element);
            }
        }
    });

    $('#group_edit_form').validate({
        rules: {
            name: {
                required: true,
                letters_with_spaces: true,
            },
            display_name: {
                required: true,
                letters_with_spaces: true,
            },
            description: {
                required: true,
                letters_with_spaces: true,
            },
            "permission[]": {
                required: true,
                minlength: 1
            },
        },
        messages: {
            "permission[]": {
                required: "Please select at least one checkbox!",
            },
        },
    });
</script>

@endpush
