<style>
.skeleton-box {
display: inline-block;
height: 1em;
position: relative;
overflow: hidden;
background-color: #DDDBDD;

&::after {
position: absolute;
top: 0;
right: 0;
bottom: 0;
left: 0;
transform: translateX(-100%);
background-image: linear-gradient(
90deg,
rgba(#fff, 0) 0,
rgba(#fff, 0.2) 20%,
rgba(#fff, 0.5) 60%,
rgba(#fff, 0)
);
animation: shimmer 5s infinite;
content: '';
}

@keyframes shimmer {
100% {
transform: translateX(100%);
}
}
}

ul.o-vertical-spacing.o-vertical-spacing--l {
    list-style: none;
}

.blog-post {
&__headline {
font-size: 1.25em;
font-weight: bold;
}

&__meta {
font-size: 0.85em;
color: #6b6b6b;
}
}
// OBJECTS

.o-media {
display: flex;

&__body {
flex-grow: 1;
margin-left: 1em;
}
}

.o-vertical-spacing {
> * + * {
margin-top: 0.75em;
}

&--l {
> * + * {
margin-top: 2em;
}
}
}
</style>



<ul class="o-vertical-spacing o-vertical-spacing--l">

<li class="blog-post o-media">
<div class="o-media__figure">
<span class="skeleton-box" style="width:100px;height:80px;"></span>
</div>
<div class="o-media__body">
<div class="o-vertical-spacing">
<h3 class="blog-post__headline">
<span class="skeleton-box" style="width:55%;"></span>
</h3>
<p>
<span class="skeleton-box" style="width:80%;"></span>
<span class="skeleton-box" style="width:90%;"></span>
<span class="skeleton-box" style="width:83%;"></span>
<span class="skeleton-box" style="width:80%;"></span>
</p>
<div class="blog-post__meta">
<span class="skeleton-box" style="width:70px;"></span>
</div>
</div>
</div>
</li>

</ul>

