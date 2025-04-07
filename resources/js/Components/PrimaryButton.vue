<template>
    <button
        class= "btn btn-sm btn-primary">
        <slot />
    </button>
</template>

<style scoped>
.btn {
    background-color: #1abc9c;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 30px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background-color: #8be4d3;
    color: #fff;
    border: 2px solid #1abc9c;
}

.btn-disabled {
    background-color: #ccc;
    color: #666;
    cursor: not-allowed;
    border: none;
    padding: 10px 15px;
    border-radius: 30px;
    pointer-events: none; /* Empêche le clic */
}
</style>
