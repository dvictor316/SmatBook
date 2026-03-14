 <!-- All Invoice -->
 <div class="card invoices-tabs-card">
     <div class="invoices-main-tabs">
         <div class="row align-items-center">
             <div class="col-lg-12">
                 <div class="invoices-tabs">
                     <ul>
                         <li><a href="{{ url('product-list') }}"
                                 class="{{ Request::is('product-list') ? 'active' : '' }}">Product</a></li>
                         <li><a href="{{ Route::has('categories.index') ? route('categories.index') : url('categories') }}"
                                 class="{{ Request::is('categories', 'categories/*', 'category') ? 'active' : '' }}">Category</a></li>
                         <li><a href="{{ url('units') }}" class="{{ Request::is('units') ? 'active' : '' }}">Units</a>
                         </li>
                     </ul>
                 </div>
             </div>
         </div>
     </div>
 </div>
 <!-- /All Invoice -->
