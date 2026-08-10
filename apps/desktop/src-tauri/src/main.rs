use tauri::{CustomMenuItem, Menu, MenuItem, Submenu};

fn main() {
    let navigation_menu = Menu::new()
        .add_item(CustomMenuItem::new("nav_back".to_string(), "Back").accelerator("Alt+Left"))
        .add_item(CustomMenuItem::new("nav_forward".to_string(), "Forward").accelerator("Alt+Right"))
        .add_native_item(MenuItem::Separator)
        .add_item(CustomMenuItem::new("nav_reload".to_string(), "Reload").accelerator("CommandOrControl+R"))
        .add_item(CustomMenuItem::new("nav_home".to_string(), "Home").accelerator("CommandOrControl+H"));

    let menu = Menu::new().add_submenu(Submenu::new("Navigation", navigation_menu));

    tauri::Builder::default()
        .menu(menu)
        .on_menu_event(|event| {
            let script = match event.menu_item_id() {
                "nav_back" => "if (window.history.length > 1) window.history.back();",
                "nav_forward" => "window.history.forward();",
                "nav_reload" => "window.location.reload();",
                "nav_home" => "window.location.href = 'https://smartprobook.com';",
                _ => return,
            };

            let _ = event.window().eval(script);
        })
        .run(tauri::generate_context!())
        .expect("failed to run SmartProbook desktop app");
}
