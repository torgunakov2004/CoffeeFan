<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeFan - Игровой Центр</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <style>
        body {
            background-color: #14110E; /* Фоновый цвет страницы */
            color: #FFFFFF;
            font-family: 'Urbanist', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin:0;
            padding: 0;
            position: relative; /* Для корректного позиционирования canvas */
            overflow-x: hidden; /* Предотвратить горизонтальный скролл из-за canvas */
        }

        /* Стили для canvas эффекта курсора */
        #fluid-canvas {
            position: fixed; /* Или absolute, если body имеет overflow:hidden */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Поместить canvas за всем остальным контентом */
        }

        main.game-hub-main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            position: relative; /* Чтобы контент был поверх canvas */
            z-index: 1; /* Убедимся, что контент выше canvas */
        }
        .game-hub-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 50px 20px;
            text-align: center;
        }
        .game-hub-title {
            font-family: 'Righteous', cursive;
            color: #C99E71;
            font-size: 2.8em;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .game-selection {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 35px;
            margin-bottom: 50px;
        }
        .game-card {
            background-color: rgba(28, 24, 20, 0.85); 
            border: 2px solid #3a3531;
            border-radius: 12px;
            padding: 25px;
            width: 100%;
            max-width: 320px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .game-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 10px 25px rgba(201, 158, 113, 0.3);
            border-color: #C99E71;
        }
        .game-card img.game-preview {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #444;
        }
        .game-card h3 {
            font-family: 'Righteous', cursive;
            color: #FFFFFF;
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.6em;
            line-height: 1.3;
        }
        .game-card p {
            font-size: 0.95em;
            color: #ccc;
            line-height: 1.6;
            min-height: 70px;
            margin-bottom: 0;
        }
        .navigation-links-hub {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .back-to-site-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background-color: #C99E71;
            color: #14110E;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 1em;
            min-width: 200px;
            justify-content: center;
        }
        .back-to-site-btn:hover {
            background-color: #bd864b;
            transform: translateY(-2px);
        }
        .back-to-site-btn .material-icons-outlined {
            font-size: 1.3em;
        }
        @media (max-width: 768px) {
            .game-hub-title { font-size: 2.2em; margin-bottom: 30px; }
            .game-card { max-width: 280px; padding: 20px;}
            .game-card h3 { font-size: 1.4em; }
            .game-card p { font-size: 0.9em; min-height: 60px; }
        }
         @media (max-width: 600px) {
            .game-selection { flex-direction: column; align-items: center; gap: 25px; }
            .game-card { max-width: 90%; }
        }
    </style>
</head>
<body>
    <canvas id="fluid-canvas"></canvas>

    <main class="game-hub-main">
        <div class="game-hub-container">
            <h1 class="game-hub-title">Игровой Центр CoffeeFan</h1>
            <div class="game-selection">
                <div class="game-card" onclick="window.location.href='/games/cringe_attack/';">
                    <img src="/games/cringe_attack/img/game_preview_cringe_attack.png" alt="Атака Кринж-Кофе" class="game-preview">
                    <h3>Атака Кринж-Кофе!</h3>
                    <p>Защити качественный кофе от нашествия кринжовых зерен! Динамичный шутер для настоящих кофеманов.</p>
                </div>
                <div class="game-card" onclick="window.location.href='/games/coffee_quest/';">
                     <img src="/games/coffee_quest/img/game_preview_coffee_quest.png" alt="Coffee Quest" class="game-preview">
                    <h3>Coffee Quest</h3>
                    <p>Отправляйтесь в увлекательное приключение в мире кофе, собирайте зерна и преодолевайте препятствия.</p>
                </div>
            </div>
            <div class="navigation-links-hub">
                <a href="/index.php" class="back-to-site-btn">
                    <span class="material-icons-outlined">home</span>На главную сайта
                </a>
            </div>
        </div>
    </main>

<script>
    'use strict';

const canvas = document.getElementById('fluid-canvas');
if (canvas) {
    canvas.width = canvas.clientWidth;
    canvas.height = canvas.clientHeight;

    let config = {
      TEXTURE_DOWNSAMPLE: 1,
      DENSITY_DISSIPATION: 0.98,
      VELOCITY_DISSIPATION: 0.99,
      PRESSURE_DISSIPATION: 0.8,
      PRESSURE_ITERATIONS: 25,
      CURL: 28,
      SPLAT_RADIUS: 0.004 
    };

    let pointers = [];
    let splatStack = [];

    const { gl, ext } = getWebGLContext(canvas);

    function getWebGLContext(canvas) {
      const params = { alpha: false, depth: false, stencil: false, antialias: false };
      let gl = canvas.getContext('webgl2', params);
      const isWebGL2 = !!gl;
      if (!isWebGL2)
      gl = canvas.getContext('webgl', params) || canvas.getContext('experimental-webgl', params);

      let halfFloat;
      let supportLinearFiltering;
      if (isWebGL2) {
        gl.getExtension('EXT_color_buffer_float');
        supportLinearFiltering = gl.getExtension('OES_texture_float_linear');
      } else {
        halfFloat = gl.getExtension('OES_texture_half_float');
        supportLinearFiltering = gl.getExtension('OES_texture_half_float_linear');
      }

      gl.clearColor(0.0, 0.0, 0.0, 1.0); 

      const halfFloatTexType = isWebGL2 ? gl.HALF_FLOAT : halfFloat.HALF_FLOAT_OES;
      let formatRGBA;
      let formatRG;
      let formatR;

      if (isWebGL2) {
        formatRGBA = getSupportedFormat(gl, gl.RGBA16F, gl.RGBA, halfFloatTexType);
        formatRG = getSupportedFormat(gl, gl.RG16F, gl.RG, halfFloatTexType);
        formatR = getSupportedFormat(gl, gl.R16F, gl.RED, halfFloatTexType);
      } else {
        formatRGBA = getSupportedFormat(gl, gl.RGBA, gl.RGBA, halfFloatTexType);
        formatRG = getSupportedFormat(gl, gl.RGBA, gl.RGBA, halfFloatTexType);
        formatR = getSupportedFormat(gl, gl.RGBA, gl.RGBA, halfFloatTexType);
      }

      if (!formatRGBA || !formatRG || !formatR) {
          console.error("WebGL Error: Required texture formats not supported.");
          return { gl: null, ext: null }; 
      }

      return {
        gl,
        ext: {
          formatRGBA,
          formatRG,
          formatR,
          halfFloatTexType,
          supportLinearFiltering } };
    }

    function getSupportedFormat(gl, internalFormat, format, type) {
      if (!supportRenderTextureFormat(gl, internalFormat, format, type)) {
        switch (internalFormat) {
          case gl.R16F: return getSupportedFormat(gl, gl.RG16F, gl.RG, type);
          case gl.RG16F: return getSupportedFormat(gl, gl.RGBA16F, gl.RGBA, type);
          default: return null;
        }
      }
      return { internalFormat, format };
    }

    function supportRenderTextureFormat(gl, internalFormat, format, type) {
      let texture = gl.createTexture();
      gl.bindTexture(gl.TEXTURE_2D, texture);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
      gl.texImage2D(gl.TEXTURE_2D, 0, internalFormat, 4, 4, 0, format, type, null);
      let fbo = gl.createFramebuffer();
      gl.bindFramebuffer(gl.FRAMEBUFFER, fbo);
      gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, texture, 0);
      const status = gl.checkFramebufferStatus(gl.FRAMEBUFFER);
      gl.deleteTexture(texture); 
      gl.deleteFramebuffer(fbo); 
      return status == gl.FRAMEBUFFER_COMPLETE;
    }

    function pointerPrototype() {
      this.id = -1;
      this.x = 0;
      this.y = 0;
      this.dx = 0;
      this.dy = 0;
      this.down = false;
      this.moved = false;
      this.color = [30, 0, 300]; 
    }

    pointers.push(new pointerPrototype());

    class GLProgram {
      constructor(vertexShader, fragmentShader) {
        this.uniforms = {};
        this.program = gl.createProgram();
        gl.attachShader(this.program, vertexShader);
        gl.attachShader(this.program, fragmentShader);
        gl.linkProgram(this.program);
        if (!gl.getProgramParameter(this.program, gl.LINK_STATUS)) throw gl.getProgramInfoLog(this.program);
        const uniformCount = gl.getProgramParameter(this.program, gl.ACTIVE_UNIFORMS);
        for (let i = 0; i < uniformCount; i++) {
          const uniformName = gl.getActiveUniform(this.program, i).name;
          this.uniforms[uniformName] = gl.getUniformLocation(this.program, uniformName);
        }
      }
      bind() { gl.useProgram(this.program); }
    }

    function compileShader(type, source) {
      const shader = gl.createShader(type);
      gl.shaderSource(shader, source);
      gl.compileShader(shader);
      if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) throw gl.getShaderInfoLog(shader);
      return shader;
    };

    if (gl && ext) {
        const baseVertexShader = compileShader(gl.VERTEX_SHADER, `precision highp float; precision mediump sampler2D; attribute vec2 aPosition; varying vec2 vUv; varying vec2 vL; varying vec2 vR; varying vec2 vT; varying vec2 vB; uniform vec2 texelSize; void main () { vUv = aPosition * 0.5 + 0.5; vL = vUv - vec2(texelSize.x, 0.0); vR = vUv + vec2(texelSize.x, 0.0); vT = vUv + vec2(0.0, texelSize.y); vB = vUv - vec2(0.0, texelSize.y); gl_Position = vec4(aPosition, 0.0, 1.0); }`);
        const clearShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; uniform sampler2D uTexture; uniform float value; void main () { gl_FragColor = value * texture2D(uTexture, vUv); }`);
        const displayShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; uniform sampler2D uTexture; void main () { gl_FragColor = texture2D(uTexture, vUv); }`);
        const splatShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; uniform sampler2D uTarget; uniform float aspectRatio; uniform vec3 color; uniform vec2 point; uniform float radius; void main () { vec2 p = vUv - point.xy; p.x *= aspectRatio; vec3 splat = exp(-dot(p, p) / radius) * color; vec3 base = texture2D(uTarget, vUv).xyz; gl_FragColor = vec4(base + splat, 1.0); }`);
        const advectionManualFilteringShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; uniform sampler2D uVelocity; uniform sampler2D uSource; uniform vec2 texelSize; uniform float dt; uniform float dissipation; vec4 bilerp (in sampler2D sam, in vec2 p) { vec4 st; st.xy = floor(p - 0.5) + 0.5; st.zw = st.xy + 1.0; vec4 uv = st * texelSize.xyxy; vec4 a = texture2D(sam, uv.xy); vec4 b = texture2D(sam, uv.zy); vec4 c = texture2D(sam, uv.xw); vec4 d = texture2D(sam, uv.zw); vec2 f = p - st.xy; return mix(mix(a, b, f.x), mix(c, d, f.x), f.y); } void main () { vec2 coord = gl_FragCoord.xy - dt * texture2D(uVelocity, vUv).xy; gl_FragColor = dissipation * bilerp(uSource, coord); gl_FragColor.a = 1.0; }`);
        const advectionShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; uniform sampler2D uVelocity; uniform sampler2D uSource; uniform vec2 texelSize; uniform float dt; uniform float dissipation; void main () { vec2 coord = vUv - dt * texture2D(uVelocity, vUv).xy * texelSize; gl_FragColor = dissipation * texture2D(uSource, coord); gl_FragColor.a = 1.0; }`);
        const divergenceShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; varying vec2 vL; varying vec2 vR; varying vec2 vT; varying vec2 vB; uniform sampler2D uVelocity; vec2 sampleVelocity (in vec2 uv) { vec2 multiplier = vec2(1.0, 1.0); if (uv.x < 0.0) { uv.x = 0.0; multiplier.x = -1.0; } if (uv.x > 1.0) { uv.x = 1.0; multiplier.x = -1.0; } if (uv.y < 0.0) { uv.y = 0.0; multiplier.y = -1.0; } if (uv.y > 1.0) { uv.y = 1.0; multiplier.y = -1.0; } return multiplier * texture2D(uVelocity, uv).xy; } void main () { float L = sampleVelocity(vL).x; float R = sampleVelocity(vR).x; float T = sampleVelocity(vT).y; float B = sampleVelocity(vB).y; float div = 0.5 * (R - L + T - B); gl_FragColor = vec4(div, 0.0, 0.0, 1.0); }`);
        const curlShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; varying vec2 vL; varying vec2 vR; varying vec2 vT; varying vec2 vB; uniform sampler2D uVelocity; void main () { float L = texture2D(uVelocity, vL).y; float R = texture2D(uVelocity, vR).y; float T = texture2D(uVelocity, vT).x; float B = texture2D(uVelocity, vB).x; float vorticity = R - L - T + B; gl_FragColor = vec4(vorticity, 0.0, 0.0, 1.0); }`);
        const vorticityShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; varying vec2 vT; varying vec2 vB; uniform sampler2D uVelocity; uniform sampler2D uCurl; uniform float curl; uniform float dt; void main () { float T = texture2D(uCurl, vT).x; float B = texture2D(uCurl, vB).x; float C = texture2D(uCurl, vUv).x; vec2 force = vec2(abs(T) - abs(B), 0.0); force *= 1.0 / length(force + 0.00001) * curl * C; vec2 vel = texture2D(uVelocity, vUv).xy; gl_FragColor = vec4(vel + force * dt, 0.0, 1.0); }`);
        const pressureShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; varying vec2 vL; varying vec2 vR; varying vec2 vT; varying vec2 vB; uniform sampler2D uPressure; uniform sampler2D uDivergence; vec2 boundary (in vec2 uv) { uv = min(max(uv, 0.0), 1.0); return uv; } void main () { float L = texture2D(uPressure, boundary(vL)).x; float R = texture2D(uPressure, boundary(vR)).x; float T = texture2D(uPressure, boundary(vT)).x; float B = texture2D(uPressure, boundary(vB)).x; float C = texture2D(uPressure, vUv).x; float divergence = texture2D(uDivergence, vUv).x; float pressure = (L + R + B + T - divergence) * 0.25; gl_FragColor = vec4(pressure, 0.0, 0.0, 1.0); }`);
        const gradientSubtractShader = compileShader(gl.FRAGMENT_SHADER, `precision highp float; precision mediump sampler2D; varying vec2 vUv; varying vec2 vL; varying vec2 vR; varying vec2 vT; varying vec2 vB; uniform sampler2D uPressure; uniform sampler2D uVelocity; vec2 boundary (in vec2 uv) { uv = min(max(uv, 0.0), 1.0); return uv; } void main () { float L = texture2D(uPressure, boundary(vL)).x; float R = texture2D(uPressure, boundary(vR)).x; float T = texture2D(uPressure, boundary(vT)).x; float B = texture2D(uPressure, boundary(vB)).x; vec2 velocity = texture2D(uVelocity, vUv).xy; velocity.xy -= vec2(R - L, T - B); gl_FragColor = vec4(velocity, 0.0, 1.0); }`);

        let textureWidth;
        let textureHeight;
        let density;
        let velocity;
        let divergence;
        let curl;
        let pressure;
        initFramebuffers();

        const clearProgram = new GLProgram(baseVertexShader, clearShader);
        const displayProgram = new GLProgram(baseVertexShader, displayShader);
        const splatProgram = new GLProgram(baseVertexShader, splatShader);
        const advectionProgram = new GLProgram(baseVertexShader, ext.supportLinearFiltering ? advectionShader : advectionManualFilteringShader);
        const divergenceProgram = new GLProgram(baseVertexShader, divergenceShader);
        const curlProgram = new GLProgram(baseVertexShader, curlShader);
        const vorticityProgram = new GLProgram(baseVertexShader, vorticityShader);
        const pressureProgram = new GLProgram(baseVertexShader, pressureShader);
        const gradienSubtractProgram = new GLProgram(baseVertexShader, gradientSubtractShader);

        function initFramebuffers() {
          textureWidth = gl.drawingBufferWidth >> config.TEXTURE_DOWNSAMPLE;
          textureHeight = gl.drawingBufferHeight >> config.TEXTURE_DOWNSAMPLE;
          const texType = ext.halfFloatTexType;
          const rgba = ext.formatRGBA;
          const rg = ext.formatRG;
          const r = ext.formatR;
          if (!rgba || !rg || !r) { console.error("Cannot initialize framebuffers: texture formats not supported."); return; }
          density = createDoubleFBO(2, textureWidth, textureHeight, rgba.internalFormat, rgba.format, texType, ext.supportLinearFiltering ? gl.LINEAR : gl.NEAREST);
          velocity = createDoubleFBO(0, textureWidth, textureHeight, rg.internalFormat, rg.format, texType, ext.supportLinearFiltering ? gl.LINEAR : gl.NEAREST);
          divergence = createFBO(4, textureWidth, textureHeight, r.internalFormat, r.format, texType, gl.NEAREST);
          curl = createFBO(5, textureWidth, textureHeight, r.internalFormat, r.format, texType, gl.NEAREST);
          pressure = createDoubleFBO(6, textureWidth, textureHeight, r.internalFormat, r.format, texType, gl.NEAREST);
        }

        function createFBO(texId, w, h, internalFormat, format, type, param) {
          gl.activeTexture(gl.TEXTURE0 + texId);
          let texture = gl.createTexture();
          gl.bindTexture(gl.TEXTURE_2D, texture);
          gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, param);
          gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, param);
          gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
          gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
          gl.texImage2D(gl.TEXTURE_2D, 0, internalFormat, w, h, 0, format, type, null);
          let fbo = gl.createFramebuffer();
          gl.bindFramebuffer(gl.FRAMEBUFFER, fbo);
          gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, texture, 0);
          gl.viewport(0, 0, w, h);
          gl.clear(gl.COLOR_BUFFER_BIT);
          return [texture, fbo, texId];
        }

        function createDoubleFBO(texId, w, h, internalFormat, format, type, param) {
          let fbo1 = createFBO(texId, w, h, internalFormat, format, type, param);
          let fbo2 = createFBO(texId + 1, w, h, internalFormat, format, type, param);
          return { get read() { return fbo1; }, get write() { return fbo2; }, swap() { let temp = fbo1; fbo1 = fbo2; fbo2 = temp; } };
        }

        const blit = (() => {
          gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
          gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, -1, 1, 1, 1, 1, -1]), gl.STATIC_DRAW);
          gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, gl.createBuffer());
          gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array([0, 1, 2, 0, 2, 3]), gl.STATIC_DRAW);
          gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);
          gl.enableVertexAttribArray(0);
          return destination => { gl.bindFramebuffer(gl.FRAMEBUFFER, destination); gl.drawElements(gl.TRIANGLES, 6, gl.UNSIGNED_SHORT, 0); };
        })();

        let lastTime = Date.now();
        multipleSplats(parseInt(Math.random() * 20) + 5);
        update();

        function update() {
          if (!gl) { return; }
          resizeCanvas();
          const dt = Math.min((Date.now() - lastTime) / 1000, 0.016);
          lastTime = Date.now();
          gl.viewport(0, 0, textureWidth, textureHeight);
          if (splatStack.length > 0) multipleSplats(splatStack.pop());
          advectionProgram.bind();
          gl.uniform2f(advectionProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(advectionProgram.uniforms.uVelocity, velocity.read[2]);
          gl.uniform1i(advectionProgram.uniforms.uSource, velocity.read[2]);
          gl.uniform1f(advectionProgram.uniforms.dt, dt);
          gl.uniform1f(advectionProgram.uniforms.dissipation, config.VELOCITY_DISSIPATION);
          blit(velocity.write[1]);
          velocity.swap();
          gl.uniform1i(advectionProgram.uniforms.uVelocity, velocity.read[2]);
          gl.uniform1i(advectionProgram.uniforms.uSource, density.read[2]);
          gl.uniform1f(advectionProgram.uniforms.dissipation, config.DENSITY_DISSIPATION);
          blit(density.write[1]);
          density.swap();
          for (let i = 0; i < pointers.length; i++) {
            const pointer = pointers[i];
            if (pointer.moved) {
              splat(pointer.x, pointer.y, pointer.dx, pointer.dy, pointer.color);
              pointer.moved = false;
            }
          }
          curlProgram.bind();
          gl.uniform2f(curlProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(curlProgram.uniforms.uVelocity, velocity.read[2]);
          blit(curl[1]);
          vorticityProgram.bind();
          gl.uniform2f(vorticityProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(vorticityProgram.uniforms.uVelocity, velocity.read[2]);
          gl.uniform1i(vorticityProgram.uniforms.uCurl, curl[2]);
          gl.uniform1f(vorticityProgram.uniforms.curl, config.CURL);
          gl.uniform1f(vorticityProgram.uniforms.dt, dt);
          blit(velocity.write[1]);
          velocity.swap();
          divergenceProgram.bind();
          gl.uniform2f(divergenceProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(divergenceProgram.uniforms.uVelocity, velocity.read[2]);
          blit(divergence[1]);
          clearProgram.bind();
          let pressureTexId = pressure.read[2];
          gl.activeTexture(gl.TEXTURE0 + pressureTexId);
          gl.bindTexture(gl.TEXTURE_2D, pressure.read[0]);
          gl.uniform1i(clearProgram.uniforms.uTexture, pressureTexId);
          gl.uniform1f(clearProgram.uniforms.value, config.PRESSURE_DISSIPATION);
          blit(pressure.write[1]);
          pressure.swap();
          pressureProgram.bind();
          gl.uniform2f(pressureProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(pressureProgram.uniforms.uDivergence, divergence[2]);
          pressureTexId = pressure.read[2];
          gl.uniform1i(pressureProgram.uniforms.uPressure, pressureTexId);
          gl.activeTexture(gl.TEXTURE0 + pressureTexId);
          for (let i = 0; i < config.PRESSURE_ITERATIONS; i++) {
            gl.bindTexture(gl.TEXTURE_2D, pressure.read[0]);
            blit(pressure.write[1]);
            pressure.swap();
          }
          gradienSubtractProgram.bind();
          gl.uniform2f(gradienSubtractProgram.uniforms.texelSize, 1.0 / textureWidth, 1.0 / textureHeight);
          gl.uniform1i(gradienSubtractProgram.uniforms.uPressure, pressure.read[2]);
          gl.uniform1i(gradienSubtractProgram.uniforms.uVelocity, velocity.read[2]);
          blit(velocity.write[1]);
          velocity.swap();
          gl.viewport(0, 0, gl.drawingBufferWidth, gl.drawingBufferHeight);
          displayProgram.bind();
          gl.uniform1i(displayProgram.uniforms.uTexture, density.read[2]);
          blit(null);
          requestAnimationFrame(update);
        }

        function splat(x, y, dx, dy, color) {
          splatProgram.bind();
          gl.uniform1i(splatProgram.uniforms.uTarget, velocity.read[2]);
          gl.uniform1f(splatProgram.uniforms.aspectRatio, canvas.width / canvas.height);
          gl.uniform2f(splatProgram.uniforms.point, x / canvas.width, 1.0 - y / canvas.height);
          gl.uniform3f(splatProgram.uniforms.color, dx, -dy, 1.0); 
          gl.uniform1f(splatProgram.uniforms.radius, config.SPLAT_RADIUS);
          blit(velocity.write[1]);
          velocity.swap();
          gl.uniform1i(splatProgram.uniforms.uTarget, density.read[2]);
          gl.uniform3f(splatProgram.uniforms.color, color[0] * 0.02, color[1] * 0.02, color[2] * 0.02);
          blit(density.write[1]);
          density.swap();
        }

        function multipleSplats(amount) {
          for (let i = 0; i < amount; i++) {
            const color = [Math.random() * 5 + 1, Math.random() * 5 + 1, Math.random() * 10 + 5]; 
            const x = canvas.width * Math.random();
            const y = canvas.height * Math.random();
            const dx = 1000 * (Math.random() - 0.5);
            const dy = 1000 * (Math.random() - 0.5);
            splat(x, y, dx, dy, color);
          }
        }

        function resizeCanvas() {
          if (canvas.width != canvas.clientWidth || canvas.height != canvas.clientHeight) {
            canvas.width = canvas.clientWidth;
            canvas.height = canvas.clientHeight;
             if (gl && ext && density) { 
                initFramebuffers();
             }
          }
        }

        document.addEventListener('mousemove', e => {
          pointers[0].moved = true; // Always true on move
          pointers[0].dx = (e.clientX - pointers[0].x) * 10.0;
          pointers[0].dy = (e.clientY - pointers[0].y) * 10.0;
          pointers[0].x = e.clientX;
          pointers[0].y = e.clientY;
          pointers[0].color = [ // Set color on move
                (Math.random() * 50 + 150), 
                (Math.random() * 50 + 100), 
                (Math.random() * 30 + 50)
            ];
        });

        document.addEventListener('mousedown', () => {
            // Mouse down no longer controls drawing activation for mouse
        });
        
        document.addEventListener('mouseup', () => {
            // Mouse up no longer controls drawing deactivation for mouse
        });

        canvas.addEventListener('touchmove', e => {
          e.preventDefault();
          const touches = e.targetTouches;
          for (let i = 0; i < touches.length; i++) {
            let pointer = pointers[i];
            if (!pointer) pointers[i] = pointer = new pointerPrototype();
            pointer.moved = pointer.down; // For touch, .down still matters
            pointer.dx = (touches[i].pageX - pointer.x) * 10.0;
            pointer.dy = (touches[i].pageY - pointer.y) * 10.0;
            pointer.x = touches[i].pageX;
            pointer.y = touches[i].pageY;
          }
        }, false);

        canvas.addEventListener('touchstart', e => {
          e.preventDefault();
          const touches = e.targetTouches;
          for (let i = 0; i < touches.length; i++) {
            if (i >= pointers.length) pointers.push(new pointerPrototype());
            pointers[i].id = touches[i].identifier;
            pointers[i].down = true; // .down is set for touch
            pointers[i].x = touches[i].pageX;
            pointers[i].y = touches[i].pageY;
            pointers[i].color = [
                (Math.random() * 50 + 150), 
                (Math.random() * 50 + 100), 
                (Math.random() * 30 + 50)
            ];
          }
        });

        window.addEventListener('mouseup', () => { 
          pointers[0].down = false; 
        });

        window.addEventListener('touchend', e => {
          const touches = e.changedTouches;
          for (let i = 0; i < touches.length; i++)
          for (let j = 0; j < pointers.length; j++)
          if (touches[i].identifier == pointers[j].id)
          pointers[j].down = false; // .down is reset for touch
        });
    } else {
        console.warn("WebGL context could not be initialized. Fluid cursor effect disabled.");
    }
} else {
    console.error("Canvas element with ID 'fluid-canvas' not found.");
}
</script>

</body>
</html>