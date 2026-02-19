<?php $page = 'reset-password'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="login-wrapper">
        <div class="container">
            <div class="loginbox">
                <div class="login-right">
                    <div class="login-right-wrap">
                        <h1>Set New Password</h1>
                        <p class="account-subtitle">Create a strong password for your account</p>

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">
                            <input type="hidden" name="email" value="{{ $request->email }}">

                            <div class="input-block mb-3">
                                <label class="form-control-label">New Password</label>
                                <input class="form-control @error('password') is-invalid @enderror" 
                                       type="password" name="password" required autofocus>
                                @error('password')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="input-block mb-3">
                                <label class="form-control-label">Confirm New Password</label>
                                <input class="form-control" type="password" name="password_confirmation" required>
                            </div>

                            <div class="input-block mb-0">
                                <button class="btn btn-lg btn-primary w-100" type="submit">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection