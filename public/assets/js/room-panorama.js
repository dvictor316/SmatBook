// Local WebGL renderer for full 2:1 equirectangular room panoramas.
// Returns null when hardware acceleration is unavailable: the photo viewer remains usable.
export function createPanorama(canvas, image) {
    const gl = canvas.getContext('webgl', { alpha: false, antialias: false, preserveDrawingBuffer: false });
    if (!gl) return null;
    let program, texture, buffer;
    const shaders = [];
    try {
        const shader = (type, source) => {
            const result = gl.createShader(type);
            shaders.push(result);
            gl.shaderSource(result, source);
            gl.compileShader(result);
            if (!gl.getShaderParameter(result, gl.COMPILE_STATUS)) throw new Error('Panorama shader unavailable');
            return result;
        };
        program = gl.createProgram();
        gl.attachShader(program, shader(gl.VERTEX_SHADER, 'attribute vec2 position; varying vec2 screen; void main(){screen=position;gl_Position=vec4(position,0.0,1.0);}'));
        gl.attachShader(program, shader(gl.FRAGMENT_SHADER, `
            precision mediump float;
            varying vec2 screen;
            uniform sampler2D photo;
            uniform float aspect, lens, yaw, pitch;
            void main(){
                vec3 ray=normalize(vec3(screen.x*aspect*lens,screen.y*lens,1.0));
                ray=vec3(ray.x,ray.y*cos(pitch)+ray.z*sin(pitch),-ray.y*sin(pitch)+ray.z*cos(pitch));
                ray=vec3(ray.x*cos(yaw)+ray.z*sin(yaw),ray.y,-ray.x*sin(yaw)+ray.z*cos(yaw));
                vec2 uv=vec2(fract(atan(ray.x,ray.z)/6.2831853+0.5),0.5-asin(clamp(ray.y,-1.0,1.0))/3.14159265);
                gl_FragColor=texture2D(photo,uv);
            }
        `));
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error('Panorama program unavailable');
        gl.useProgram(program);
        buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,-1,1,1,-1,1,1]), gl.STATIC_DRAW);
        const position = gl.getAttribLocation(program, 'position');
        gl.enableVertexAttribArray(position);
        gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
        texture = gl.createTexture();
        gl.bindTexture(gl.TEXTURE_2D, texture);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
        // Keep large uploads within the device's GPU texture limit, especially on tablets.
        const limit = Math.min(4096, gl.getParameter(gl.MAX_TEXTURE_SIZE));
        let source = image;
        if (image.naturalWidth > limit || image.naturalHeight > limit) {
            source = document.createElement('canvas');
            const ratio = limit / Math.max(image.naturalWidth, image.naturalHeight);
            source.width = Math.round(image.naturalWidth * ratio);
            source.height = Math.round(image.naturalHeight * ratio);
            source.getContext('2d').drawImage(image, 0, 0, source.width, source.height);
        }
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, source);
        if (gl.getError() !== gl.NO_ERROR) throw new Error('Panorama texture unavailable');
        const uniforms = Object.fromEntries(['aspect', 'lens', 'yaw', 'pitch'].map(name => [name, gl.getUniformLocation(program, name)]));
        return {
            draw(width, height, yaw, pitch, fov) {
                const density = Math.min(window.devicePixelRatio || 1, 1.5);
                const w = Math.round(width * density), h = Math.round(height * density);
                if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; }
                gl.viewport(0, 0, w, h);
                gl.uniform1f(uniforms.aspect, width / height);
                gl.uniform1f(uniforms.lens, Math.tan(fov * Math.PI / 360));
                gl.uniform1f(uniforms.yaw, yaw * Math.PI / 180);
                gl.uniform1f(uniforms.pitch, pitch * Math.PI / 180);
                gl.drawArrays(gl.TRIANGLES, 0, 6);
            },
            destroy() { gl.deleteTexture(texture); gl.deleteBuffer(buffer); gl.deleteProgram(program); shaders.forEach(s => gl.deleteShader(s)); }
        };
    } catch (_) {
        if (texture) gl.deleteTexture(texture);
        if (buffer) gl.deleteBuffer(buffer);
        if (program) gl.deleteProgram(program);
        shaders.forEach(s => gl.deleteShader(s));
        return null;
    }
}
