<?php $page = 'media'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Media</h5>
                </div>
            </div>
            <!-- /Page Header -->
            <div class="row">
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Examples</h4>
                        </div>
                        <!-- end card header -->

                        <div class="card-body">
                            <p class="text-muted mb-4">Use few flex utilities that allow even more flexibility and
                                customization than before.</p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-5.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media heading</h5>
                                    This is some content from a media component. You can replace this with any content and
                                    adjust it as needed.
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media heading</h5>
                                    This is some content from a media component. You can replace this with any content and
                                    adjust it as needed.
                                </div>
                                <div class="flex-shrink-0 ms-3">
                                    <img src="{{ URL::asset('/assets/img/user-6.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-7.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media heading</h5>
                                    This is some content from a media component. You can replace this with any content and
                                    adjust it as needed.
                                </div>
                            </div>
                        </div>
                        <!-- end card-body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Nesting Example</h4>
                        </div>
                        <!-- end card header -->
                        <div class="card-body">
                            <p class="text-muted mb-4">Media objects can be infinitely nested, though we suggest you stop at
                                some point. Place nested <code>d-flex align-items-start</code> within the
                                <code>flex-grow-1</code> of a parent media object.</p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-5.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media heading</h5>
                                    This is some content from a media component. You can replace this with any content and
                                    adjust it as needed.
                                    <div class="d-flex align-items-start text-muted mt-3">
                                        <div class="flex-shrink-0 me-3">
                                            <img src="{{ URL::asset('/assets/img/user-6.jpg') }}" class="avatar-sm rounded"
                                                alt="...">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fs-14">Media heading</h5>
                                            This is some content from a media component. You can replace this with any
                                            content and adjust it as needed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-7.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media heading</h5>
                                    This is some content from a media component. You can replace this with any content and
                                    adjust it as needed.
                                </div>
                            </div>
                        </div>
                        <!-- end card-body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Media Alignment</h4>
                        </div>
                        <!-- end card header -->
                        <div class="card-body">
                            <p class="text-muted mb-4">The images or other media can be aligned top, middle, or bottom. The
                                default is top aligned.
                            </p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-5.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Top Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin. Cras purus odio, vestibulum in vulputate at, tempus
                                        viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia
                                        congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare vel eu
                                        leo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur
                                        ridiculus mus.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-6.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Center Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin. Cras purus odio, vestibulum in vulputate at, tempus
                                        viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia
                                        congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare vel eu
                                        leo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur
                                        ridiculus mus.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-end text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{ URL::asset('/assets/img/user-7.jpg') }}" class="avatar-sm rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Bottom Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin.Cras purus odio, vestibulum in vulputate at, tempus
                                        viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia
                                        congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare vel eu
                                        leo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur
                                        ridiculus mus.</p>
                                </div>
                            </div>
                        </div>
                        <!-- end card-body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
